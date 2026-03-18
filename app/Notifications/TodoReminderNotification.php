<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class TodoReminderNotification extends Notification
{
    use Queueable;

    protected $task;

    /**
     * Create a new notification instance.
     */
    public function __construct($task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $keyword = urlencode($this->task->title);
        // Using a focused task/todo image
        $dynamicImage = "https://images.unsplash.com/photo-1484480974693-6ff0a00bf1f1?auto=format&fit=crop&w=1200&q=80&task={$keyword}";

        return (new WebPushMessage)
            ->title("📌 Ingat Todo: {$this->task->title}!")
            ->body("Task '{$this->task->title}' akan segera dimulai. Jangan lupa dikerjakan ya!")
            ->icon('/favicon.ico')
            ->badge('/favicon.ico')
            ->image($dynamicImage)
            ->action('🚀 Buka Aplikasi', 'view_app')
            ->action('✅ Selesai', 'mark_done')
            ->tag("todo-{$this->task->id}")
            ->data([
                'id' => $this->task->id,
                'url' => '/tasks',
                'type' => 'todo_reminder'
            ]);
    }
}
