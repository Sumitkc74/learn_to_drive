<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDatabaseDuplicates extends Command
{
    protected $signature = 'database:audit-duplicates {--connection= : A configured connection; defaults to the active database} {--json : Output aggregate JSON without record values}';
    protected $description = 'Read-only audit of duplicate candidate keys and active lesson booking consistency';

    public const KEYS = [
        'users' => ['email'], 'tutorials' => ['title'], 'traffic_signs' => ['name'],
        'vision_tests' => ['testNumber'], 'questions' => ['question'], 'notices' => ['title'],
        'exam_papers' => ['name', 'language'], 'exam_information' => ['name', 'language'],
        'driving_schools' => ['name', 'address'], 'school_memberships' => ['driving_school_id', 'user_id'],
        'lesson_vehicles' => ['registration'], 'lesson_bookings' => ['active_lesson_id'],
        'driving_lessons' => ['lesson_vehicle_id', 'starts_at', 'ends_at'],
        'premium_payments' => ['gateway', 'transaction_id'],
        'learner_bookmarks' => ['user_id', 'content_type', 'content_id'],
    ];

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');
        if (!array_key_exists($connection, config('database.connections'))) {
            $this->error('Unknown database connection.');
            return self::FAILURE;
        }
        $db = DB::connection($connection);
        $schema = $db->getSchemaBuilder();
        $grammar = $db->getQueryGrammar();
        $report = [];
        foreach (self::KEYS as $table => $columns) {
            if (!$schema->hasTable($table) || !$schema->hasColumns($table, $columns)) continue;
            $query = $db->table($table);
            $numeric = ['testNumber', 'driving_school_id', 'user_id', 'lesson_vehicle_id', 'active_lesson_id', 'content_id'];
            foreach ($columns as $column) {
                $wrapped = $grammar->wrap($column);
                $expression = in_array($column, $numeric, true) ? $wrapped : "LOWER(TRIM($wrapped))";
                $query->selectRaw($expression)->groupByRaw($expression);
                // Null address is comparable, but absent identities are not duplicates.
                if ($column !== 'address') $query->whereNotNull($column);
            }
            $query->selectRaw('COUNT(*) AS duplicate_count')->havingRaw('COUNT(*) > 1');
            $stats = $db->query()->fromSub($query, 'duplicate_groups')
                ->selectRaw('COUNT(*) AS groups_count, COALESCE(SUM(duplicate_count - 1), 0) AS extra_rows')->first();
            $report[] = ['table' => $table, 'key' => implode(' + ', $columns), 'rows' => $db->table($table)->count(),
                'candidate_groups' => (int) $stats->groups_count, 'extra_rows' => (int) $stats->extra_rows];
        }
        $invalidBookings = 0;
        if ($schema->hasTable('lesson_bookings')) {
            $invalidBookings = $db->table('lesson_bookings')->where(function ($q) {
                $q->where(function ($q) { $q->where('status', 'Booked')->where(function ($q) {
                    $q->whereNull('active_lesson_id')->orWhereColumn('active_lesson_id', '!=', 'driving_lesson_id');
                }); })->orWhere(function ($q) { $q->where('status', '!=', 'Booked')->whereNotNull('active_lesson_id'); });
            })->count();
        }
        if ($this->option('json')) {
            $this->line(json_encode(['connection' => $connection, 'driver' => $db->getDriverName(), 'tables' => $report,
                'inconsistent_booking_claims' => $invalidBookings], JSON_PRETTY_PRINT));
        } else {
            $this->info('Read-only audit: '.$connection.' ('.$db->getDriverName().')');
            $this->table(['Table', 'Candidate key', 'Rows', 'Groups', 'Extra rows'], $report);
            $this->line('Inconsistent active booking claims: '.$invalidBookings);
            $this->line('Matching content titles are candidates, not proof of redundant data. No records were changed.');
        }
        return self::SUCCESS;
    }
}
