<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationWithdrawn extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $applicationId,
        public int $vacancyId,
        public string $vacancyTitle,
        public ?int $applicantId = null,
        public ?string $applicantName = null
    ) {}

    public function via($notifiable): array
    {
        // ✅ HR gets bell alert + email
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $who = $this->applicantName ? $this->applicantName : ('Applicant #' . ($this->applicantId ?? '-'));
        $base = rtrim(config('app.url'), '/');
        $actionUrl = $base . "/hr/shortlist/vacancy/{$this->vacancyId}";

        return (new MailMessage)
            ->subject("Application Withdrawn — {$this->vacancyTitle}")
            ->greeting('Hello HR,')
            ->line("**{$who}** has withdrawn their application.")
            ->line("**Vacancy:** {$this->vacancyTitle}")
            ->line("**Application ID:** {$this->applicationId}")
            ->action('Open Shortlist Board', $actionUrl)
            ->line('You may want to update your shortlist/review queue.');
    }

    public function toArray($notifiable): array
    {
        $who = $this->applicantName ? "{$this->applicantName} " : "A candidate ";

        return [
            'kind'     => 'application_withdrawn',
            'priority' => 'normal',
            'title'    => 'Application Withdrawn',
            'message'  => "{$who}withdrew their application for: {$this->vacancyTitle}",
            'url'      => "/hr/shortlist/vacancy/{$this->vacancyId}",

            'meta' => [
                'application_id' => $this->applicationId,
                'vacancy_id'     => $this->vacancyId,
                'vacancy_title'  => $this->vacancyTitle,
                'applicant_id'   => $this->applicantId,
                'applicant_name' => $this->applicantName,
            ],
        ];
    }
}
