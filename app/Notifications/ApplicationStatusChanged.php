<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public int $applicationId,
        public string $vacancyTitle,
        public string $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'application_status_changed',
            'priority' => 'normal',
            'title' => 'Application Status Updated',
            'message' => "Your application for {$this->vacancyTitle} is now: {$this->newStatus}",
            'url' => "/applications",
            'meta' => [
                'application_id' => $this->applicationId,
                'vacancy_title' => $this->vacancyTitle,
                'new_status' => $this->newStatus,
            ],
        ];
    }
}
