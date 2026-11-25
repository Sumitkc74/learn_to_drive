<?php
namespace App\Console\Commands;
use App\Models\LessonBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class MarkCompletedLessons extends Command {
    protected $signature = 'lessons:mark-completed {--dry-run : Show eligible bookings without changing them}';
    protected $description = 'Mark booked lessons whose end time has passed as Completed';
    public function handle(): int {
        $count = 0;
        // Lesson times are stored in UTC (entered in Asia/Kathmandu, converted
        // on save), so comparing ends_at against now() uses the same instant
        // the rest of the app uses (e.g. starts_at->isFuture()).
        LessonBooking::where('status', 'Booked')->whereHas('lesson', fn ($q) => $q->where('cancelled', false)->where('ends_at', '<=', now()))->orderBy('id')->chunkById(100, function ($bookings) use (&$count) {
            foreach ($bookings as $booking) DB::transaction(function () use ($booking, &$count) {
                $current = LessonBooking::lockForUpdate()->find($booking->id);
                if (!$current || $current->status !== 'Booked') return;
                // Re-check under the lock: a lesson that has not ended yet,
                // or whose lesson was cancelled, must stay Booked.
                if (!$current->lesson || $current->lesson->cancelled || $current->lesson->ends_at->isFuture()) return;
                $count++;
                // Clear the active claim, exactly like the cancel flow does:
                // a non-Booked booking must not hold an active_lesson_id.
                if (!$this->option('dry-run')) $current->update(['status' => 'Completed', 'active_lesson_id' => null]);
            });
        });
        $this->info(($this->option('dry-run') ? 'Eligible: ' : 'Completed: ').$count.' bookings.');
        return self::SUCCESS;
    }
}
