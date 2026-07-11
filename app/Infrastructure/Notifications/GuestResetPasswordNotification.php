<?php

namespace App\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class GuestResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('guest.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset your guest password')
            ->line('You are receiving this email because we received a password reset request for your guest account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in '.config('auth.passwords.customers.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
