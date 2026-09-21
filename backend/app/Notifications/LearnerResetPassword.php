<?php
namespace App\Notifications;
class LearnerResetPassword extends \Illuminate\Auth\Notifications\ResetPassword
{
    public function toMail($notifiable)
    {
        return $this->buildMailMessage(route('learn.password.reset', ['token'=>$this->token,'email'=>$notifiable->getEmailForPasswordReset()]));
    }
}
