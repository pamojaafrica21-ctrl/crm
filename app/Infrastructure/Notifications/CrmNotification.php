<?php

namespace App\Infrastructure\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('View Details', $this->actionUrl);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
        ];
    }

    public static function send(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $user->notify(new self($title, $message, $actionUrl));
    }
}
