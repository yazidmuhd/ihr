<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewResponseReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $interviewId,
        public int $applicationId,
        public int $applicantId,
        public int $vacancyId,
        public string $vacancyTitle,
        public string $response,          // confirmed | declined
        public ?string $reason = null,
        public $scheduledAt = null,       // string|Carbon|null
        public ?string $mode = null,
        public ?string $location = null,
        public ?string $meetingLink = null,
        public ?string $applicantName = null
    ) {}

    public function via($notifiable): array
    {
        // ✅ HR gets alert + email
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $base = rtrim(config('app.url'), '/');
        $actionUrl = $base . '/hr/interviews?focus=' . $this->interviewId;

        $who = $this->applicantName ?: ('Applicant #' . $this->applicantId);
        $respNice = strtoupper($this->response);

        $when = null;
        if (!empty($this->scheduledAt)) {
            try {
                $dt = Carbon::parse($this->scheduledAt)->timezone(config('app.timezone', 'UTC'));
                $when = $dt->format('D, d M Y • h:i A');
            } catch (\Throwable $e) {
                $when = (string) $this->scheduledAt;
            }
        }

        $mail = (new MailMessage)
            ->subject("Interview {$respNice} — {$who}")
            ->greeting('Hello,')
            ->line("**{$who}** has **{$this->response}** the .")
            ->line("**Vacancy:** {$this->vacancyTitle}");

        if (!empty($when)) {
            $mail->line("**Scheduled:** {$when}");
        }

        if (!empty($this->mode)) {
            $mail->line("**Mode:** " . ucfirst(str_replace(['_', '-'], ' ', $this->mode)));
        }

        if (!empty($this->location)) {
            $mail->line("**Location:** {$this->location}");
        }

        if (!empty($this->meetingLink)) {
            $mail->line("**Meeting Link:** {$this->meetingLink}");
        }

        if (!empty($this->reason)) {
            $mail->line("**Reason/Note:** {$this->reason}");
        }

        $mail->action('Open Interview', $actionUrl)
            ->line('Please review and take the next action in the HR Interviews page.');

        return $mail;
    }

    public function toArray($notifiable): array
    {
        $who = $this->applicantName ?: ('Applicant #' . $this->applicantId);

        $msg = "{$who} responded to interview for {$this->vacancyTitle}: {$this->response}";
        if ($this->reason) $msg .= " (Reason: {$this->reason})";

        return [
            'kind' => 'interview_response',
            'priority' => 'high',
            'title' => 'Interview Response',
            'message' => $msg,
            'url' => "/hr/interviews?focus={$this->interviewId}",
            'meta' => [
                'interview_id' => $this->interviewId,
                'application_id' => $this->applicationId,
                'applicant_id' => $this->applicantId,
                'vacancy_id' => $this->vacancyId,
                'vacancy_title' => $this->vacancyTitle,
                'response' => $this->response,
                'reason' => $this->reason,
                'scheduled_at' => $this->scheduledAt,
                'mode' => $this->mode,
                'location' => $this->location,
                'meeting_link' => $this->meetingLink,
                'applicant_name' => $this->applicantName,
            ],
        ];
    }
}
