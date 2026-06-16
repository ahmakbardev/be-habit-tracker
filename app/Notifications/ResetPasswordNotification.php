<?php

namespace App\Notifications;

use App\Mail\ResetPasswordMail;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $feUrl   = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');
        $resetUrl = "{$feUrl}/reset-password?token={$this->token}&email=" . urlencode($notifiable->email);

        return (new ResetPasswordMail($notifiable->name, $resetUrl))
            ->to($notifiable->email);
    }
}
