<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

// Queued: opening a vacancy notifies every applicant, which must not block the HR request
class JobVacancyPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $vacancyId,
        public string $vacancyTitle,
        public ?string $closingDate = null, // optional
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'vacancy_posted',
            'priority' => 'normal',
            'title' => 'New Job Vacancy',
            'message' => $this->closingDate
                ? "A new vacancy is open: {$this->vacancyTitle} (Closing: {$this->closingDate})"
                : "A new vacancy is open: {$this->vacancyTitle}",
            'url' => "/jobs/{$this->vacancyId}",
            'meta' => [
                'vacancy_id' => $this->vacancyId,
                'vacancy_title' => $this->vacancyTitle,
                'closing_date' => $this->closingDate,
            ],
        ];
    }
}
