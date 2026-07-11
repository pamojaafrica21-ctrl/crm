<?php

namespace App\Infrastructure\Notifications;

use App\Domain\Customers\Models\Customer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestNotification extends Notification
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
            ->greeting('Hello '.($notifiable->fullName() ?? 'Guest').',')
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('View details', $this->actionUrl);
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

    public static function send(Customer $customer, string $title, string $message, ?string $actionUrl = null): void
    {
        $customer->notify(new self($title, $message, $actionUrl));
    }
}
