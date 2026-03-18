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
        
        // Generate dynamic image from Unsplash based on habit name
        $keyword = urlencode($this->habit->name);
        // Use a consistent keyword-based image for the banner
        $dynamicImage = "https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1200&q=80&habit={$keyword}";

        return (new WebPushMessage)
            ->title("🔥 Waktunya Habit: {$this->habit->name}!")
            ->body("Sudah jam {$this->timeSlot}, ayo laksanakan habit '{$this->habit->name}' agar harimu lebih produktif!")
            ->icon('/favicon.ico')
            ->badge('/favicon.ico')
            ->image($dynamicImage)
            ->action('🚀 Buka Aplikasi', 'view_app')
            ->action('✅ Selesai', 'mark_done')
            ->tag("habit-{$this->habit->id}")
            ->data([
                'id' => $this->habit->id,
                'url' => '/habits',
                'type' => 'habit_reminder',
                'time_slot' => $this->timeSlot
            ]);
    }
}
