<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InterviewReminder extends Notification
{
    use Queueable;

    public function __construct(
        public int $interviewId,
        public string $vacancyTitle,
        public string $scheduledAtIso // ISO string for frontend formatting
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'interview_reminder',
            'priority' => 'high',
            'title' => 'Interview Reminder',
            'message' => "Reminder: Your interview for {$this->vacancyTitle} is in 3 days.",
            'url' => "/app/interviews",
            'meta' => [
                'interview_id' => $this->interviewId,
                'vacancy_title' => $this->vacancyTitle,
                'scheduled_at' => $this->scheduledAtIso,
            ],
        ];
    }
}
