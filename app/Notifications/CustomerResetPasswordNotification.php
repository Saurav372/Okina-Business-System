<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your Okina Craft password')
            ->line('We received a password reset request for your Okina Craft customer account.')
            ->action('Reset password', $url)
            ->line('This link expires in '.config('auth.passwords.customer_accounts.expire').' minutes.')
            ->line('If you did not request this, you can ignore this email.');
    }
}
