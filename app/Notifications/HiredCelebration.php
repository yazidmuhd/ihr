<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HiredCelebration extends Notification
{
    use Queueable;

    public function __construct(public string $vacancyTitle) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'hired',
            'priority' => 'celebration',
            'title' => 'Congratulations!',
            'message' => "You have been hired for: {$this->vacancyTitle}",
            'url' => '/applicant/dashboard',
            'meta' => [],
        ];
    }
}
