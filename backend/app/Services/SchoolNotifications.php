<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class SchoolNotifications {
    public static function send(int $userId, string $message): void {
        DB::table('school_notifications')->insert(['user_id'=>$userId,'message'=>$message,'created_at'=>now(),'updated_at'=>now()]);
    }
}
