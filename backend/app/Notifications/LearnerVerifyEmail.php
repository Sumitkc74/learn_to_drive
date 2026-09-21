<?php
namespace App\Notifications;
class LearnerVerifyEmail extends \Illuminate\Auth\Notifications\VerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('learn.verification.verify', now()->addMinutes(60), [
            'id'=>$notifiable->getKey(), 'hash'=>sha1($notifiable->getEmailForVerification()),
        ]);
    }
}
