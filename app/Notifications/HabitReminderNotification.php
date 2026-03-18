<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class HabitReminderNotification extends Notification
{
    use Queueable;

    protected $habit;
    protected $timeSlot;

    /**
     * Create a new notification instance.
     * 
     * @param $habit
     * @param string $timeSlot Jam yang dijadwalkan (misal "08:00")
     */
    public function __construct($habit, $timeSlot)
    {
        $this->habit = $habit;
        $this->timeSlot = $timeSlot;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        \Illuminate\Support\Facades\Log::info("Preparing WebPush for Habit: {$this->habit->name} (ID: {$this->habit->id}) to User ID: {$notifiable->id}");
        
        return (new WebPushMessage)
            ->title('Waktunya Habit!')
            ->icon('/icon-192x192.png')
            ->body("Jangan lupa '{$this->habit->name}' dijadwalkan pukul {$this->timeSlot}.")
            ->action('Buka Aplikasi', 'view_habit')
            ->data(['habit_id' => $this->habit->id, 'time_slot' => $this->timeSlot]);
    }
}
