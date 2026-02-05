<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InterviewOutcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $interviewId = null,
        public ?int $applicationId = null,
        public ?int $vacancyId = null,
        public string $vacancyTitle = 'Vacancy',
        public string $outcome = 'info',          // invited | scheduled | cancelled | hired | info
        public $scheduledAt = null,               // string|Carbon|null
        public ?string $mode = null,
        public ?string $location = null,
        public ?string $meetingLink = null,
        public ?string $extraInfo = null,
        public bool $celebrate = false
    ) {}

    public function via(object $notifiable): array
    {
        // keep simple: store in database notifications table
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title = match ($this->outcome) {
            'invited'   => '',
            'scheduled' => 'Interview Scheduled',
            'cancelled' => 'Interview Cancelled',
            'hired'     => 'Congratulations — You’re Hired!',
            default     => 'Interview Update',
        };

        $msg = match ($this->outcome) {
            'invited'   => "You’ve been invited for an interview for {$this->vacancyTitle}.",
            'scheduled' => "Your interview for {$this->vacancyTitle} has been scheduled.",
            'cancelled' => "Your interview for {$this->vacancyTitle} has been cancelled.",
            'hired'     => "You have been selected for {$this->vacancyTitle}. Please check details.",
            default     => "There is an update related to {$this->vacancyTitle}.",
        };

        return [
            'type' => 'interview',
            'title' => $title,
            'message' => $msg,

            'outcome' => $this->outcome,
            'celebrate' => $this->celebrate,

            'interview_id' => $this->interviewId,
            'application_id' => $this->applicationId,
            'vacancy_id' => $this->vacancyId,
            'vacancy_title' => $this->vacancyTitle,

            'scheduled_at' => $this->scheduledAt,
            'mode' => $this->mode,
            'location' => $this->location,
            'meeting_link' => $this->meetingLink,
            'extra_info' => $this->extraInfo,
        ];
    }
}
