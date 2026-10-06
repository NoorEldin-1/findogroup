<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperatingAlert extends Notification
{
    use Queueable;

    /**
     * @param  array{ar: string, en: string}  $title
     * @param  array{ar: string, en: string}  $body
     */
    public function __construct(public string $kind, public array $title, public array $body, public ?string $url = null, public bool $mail = false) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title['ar'].' / '.$this->title['en'])
            ->line($this->body['ar'])
            ->line($this->body['en'])
            ->when($this->url, fn (MailMessage $message): MailMessage => $message->action('Findo Group', url($this->url)));
    }

    /**
     * @return array{kind: string, title: array{ar: string, en: string}, body: array{ar: string, en: string}, url: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
