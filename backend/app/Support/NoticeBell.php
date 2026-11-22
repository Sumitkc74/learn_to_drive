<?php
namespace App\Support;
use App\Models\{Notice, NoticeRead};
use Illuminate\Support\Facades\Auth;

class NoticeBell
{
    public const PANEL_LIMIT = 5;

    public static function data(): array
    {
        $notices = Notice::visibleToLearners()->latest('id')->take(self::PANEL_LIMIT)->get();
        $user = Auth::user();
        if (!$user) return ['notices' => $notices, 'unread' => null, 'readIds' => collect()];
        $readIds = NoticeRead::where('user_id', $user->id)->pluck('notice_id');
        $unread = Notice::visibleToLearners()->whereNotIn('id', $readIds)->count();
        return ['notices' => $notices, 'unread' => $unread, 'readIds' => $readIds];
    }

    public static function markRead(int $userId, int $noticeId): void
    {
        NoticeRead::firstOrCreate(
            ['user_id' => $userId, 'notice_id' => $noticeId],
            ['read_at' => now()]
        );
    }

    public static function markAllRead(int $userId): void
    {
        $visible = Notice::visibleToLearners()->pluck('id');
        $existing = NoticeRead::where('user_id', $userId)->whereIn('notice_id', $visible)->pluck('notice_id');
        $missing = $visible->diff($existing)->map(fn ($id) => [
            'user_id' => $userId, 'notice_id' => $id, 'read_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ])->all();
        if ($missing) NoticeRead::insert($missing);
    }
}
