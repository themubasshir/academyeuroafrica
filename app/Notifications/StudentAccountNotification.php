<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public string $category = 'email',
    ) {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $preferences = $notifiable->notification_preferences ?? [];
        $emailEnabled = $preferences[$this->category] ?? $preferences['email'] ?? true;

        if ($emailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->message);
        if ($this->actionUrl) {
            $mail->action('Open Academy', $this->actionUrl);
        }

        return $mail;
    }

    /** @return array{title: string, message: string, action_url: ?string} */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'action_url' => $this->actionUrl];
    }
}
