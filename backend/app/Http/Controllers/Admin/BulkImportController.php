<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AppSetting, User, DrivingSchool, SchoolMembership, AuditLog};
use App\Support\{AdminImportCatalog as Catalog, SpreadsheetReader, SpreadsheetDownload};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BulkImportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'users');
        $definition = Catalog::type($type);
        return view('admin.bulk-import', compact('type', 'definition'));
    }

    public function template(Request $request, string $type)
    {
        $definition = Catalog::type($type);
        return SpreadsheetDownload::make($type.'-template', $definition[2], [], $request->query('format', 'csv'));
    }

    public function store(Request $request, string $type)
    {
        [$label, $model, $headers, $identity] = Catalog::type($type);
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,xlsx', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip', 'max:'.AppSetting::documentLimitKb()],
            'attachments' => ['nullable', 'array', 'max:100'],
            'attachments.*' => ['file', 'max:'.max(AppSetting::documentLimitKb(), AppSetting::imageLimitKb())],
        ]);
        $file = $request->file('file');
        try {
            $sheet = SpreadsheetReader::read($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }
        $actual = array_map(fn ($v) => trim((string) $v), array_shift($sheet) ?? []);
        if (isset($actual[0])) $actual[0] = preg_replace('/^\xEF\xBB\xBF/', '', $actual[0]);
        if ($actual !== $headers) throw ValidationException::withMessages(['file' => 'Use the exact columns and order from the template.']);
        if (count($sheet) > 500) throw ValidationException::withMessages(['file' => 'Import up to 500 rows per file.']);

        $files = [];
        foreach ($request->file('attachments', []) as $attachment) {
            $name = $attachment->getClientOriginalName();
            if (isset($files[$name])) throw ValidationException::withMessages(['attachments' => 'Attachment filenames must be unique.']);
            $files[$name] = $attachment;
        }
        // Bound the lookup to the uploaded identities instead of querying once per row.
        $existingKeys = [];
        if ($identity !== null) {
            $identityIndex = array_search($identity, $headers, true);
            $candidates = array_values(array_unique(array_map(fn ($values) =>
                $type === 'vision-tests' ? (string) (int) ($values[$identityIndex] ?? '')
                    : mb_strtolower(trim((string) ($values[$identityIndex] ?? ''))), $sheet)));
            $columns = in_array('language', $headers, true) ? [$identity, 'language'] : [$identity];
            $wrapped = DB::connection()->getQueryGrammar()->wrap($identity);
            $query = $model::select($columns)->whereIn($type === 'vision-tests' ? $identity : DB::raw("LOWER(TRIM($wrapped))"), $candidates);
            foreach ($query->get() as $existing) {
                $existingKeys[$this->identityKey($type, $existing->$identity, $existing->language)] = true;
            }
        }
        $rows = []; $errors = []; $seen = []; $duplicates = 0; $blank = 0;
        foreach ($sheet as $index => $values) {
            $line = $index + 2;
            if (!array_filter($values, fn ($v) => trim($v) !== '')) { $blank++; continue; }
            $values = array_pad($values, count($headers), '');
            if (count($values) !== count($headers)) { $errors[] = "Row $line: incorrect number of columns."; continue; }
            $row = array_combine($headers, array_map(fn ($v) => trim($v) === '' ? null : trim($v), $values));
            if (isset($row['email'])) $row['email'] = mb_strtolower($row['email']);
            $validator = Validator::make($row, Catalog::rules($type, $row));
            if ($validator->fails()) { $errors[] = "Row $line: ".$validator->errors()->first(); continue; }

            if (in_array('attachment', $headers, true)) {
                $upload = $files[$row['attachment'] ?? ''] ?? null;
                $check = Validator::make(['attachment' => $upload], ['attachment' => Catalog::mediaRules($type)]);
                if ($check->fails()) { $errors[] = "Row $line: upload the referenced image/PDF. ".$check->errors()->first(); continue; }
            }
            if (in_array($type, ['schools', 'school-memberships'], true)) {
                $email = $type === 'schools' ? $row['owner_email'] : $row['email'];
                $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->where('is_active', true)->where('role', '!=', 'Admin')->first();
                if (!$user) { $errors[] = "Row $line: an existing active non-admin account is required."; continue; }
                $row['_user_id'] = $user->id;
                if ($type === 'school-memberships' && !DrivingSchool::whereKey($row['school_id'])->where('status', 'Approved')->exists()) {
                    $errors[] = "Row $line: select an approved school ID."; continue;
                }
            }
            if ($type === 'school-memberships') {
                $key = $row['school_id'].':'.$row['_user_id'];
                $exists = SchoolMembership::where('driving_school_id', $row['school_id'])->where('user_id', $row['_user_id'])->exists();
            } else {
                $key = $this->identityKey($type, $row[$identity], $row['language'] ?? null);
                $exists = isset($existingKeys[$key]);
            }
            if ($exists || isset($seen[$key])) { $duplicates++; continue; }
            $seen[$key] = true;
            $rows[] = $row;
        }
        $summary = ['added' => 0, 'duplicate' => $duplicates, 'invalid' => count($errors), 'skipped' => $blank];
        if ($errors || (!$rows && !$duplicates)) {
            $summary['skipped'] += count($rows);
            session()->flash('import_summary', $summary);
            throw ValidationException::withMessages(['file' => $errors ?: ['The file contains no data rows.']]);
        }
        $mediaRecords = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $attachment = $row['attachment'] ?? null;
                unset($row['attachment']);
                if ($type === 'users') $row += ['password' => Hash::make(Str::random(64)), 'is_active' => true, 'profileImage' => 'dist/img/avatar.png'];
                if ($type === 'schools') {
                    $owner = $row['_user_id']; unset($row['_user_id'], $row['owner_email']);
                    $row['status'] = 'Pending';
                }
                if ($type === 'school-memberships') $row = ['driving_school_id' => $row['school_id'], 'user_id' => $row['_user_id'], 'role' => $row['role']];
                if (in_array($type, ['traffic-signs', 'vision-tests'], true)) $row['image'] = 'media';
                $record = $model::create($row);
                if ($type === 'schools') $record->memberships()->create(['user_id' => $owner, 'role' => 'Owner']);
                if ($attachment !== null) {
                    $mediaRecords[] = $record;
                    $adder = $record->addMedia($files[$attachment])->preservingOriginal();
                    if (isset($row['language'])) $adder->withCustomProperties(['language' => $row['language']]);
                    $adder->toMediaCollection();
                }
            }
            AuditLog::create(['actor_id' => $request->user()->id, 'event' => 'bulk_import.created', 'subject_type' => $model,
                'subject_label' => $label, 'new_values' => ['added' => count($rows), 'duplicates' => $duplicates]]);
            DB::commit();
        } catch (\Throwable $e) {
            foreach ($mediaRecords as $record) { try { $record->clearMediaCollection(); } catch (\Throwable $cleanup) { report($cleanup); } }
            DB::rollBack();
            report($e);
            throw ValidationException::withMessages(['file' => 'Import failed. No records were added. Check the files and try again.']);
        }
        $summary['added'] = count($rows);
        session()->flash('import_summary', $summary);
        return redirect()->route('bulkImport', ['type' => $type])->with('success', count($rows).' records imported. Existing records were not changed.');
    }

    private function identityKey(string $type, mixed $value, ?string $language): string
    {
        return json_encode([$type === 'vision-tests' ? (string) (int) $value : mb_strtolower(trim((string) $value)), $language]);
    }
}
