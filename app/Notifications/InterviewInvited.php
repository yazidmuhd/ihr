<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $interviewId,
        public int $vacancyId,
        public string $vacancyTitle,
        public string $scheduledAtIso,
        public string $mode,
        public ?string $location,
        public ?string $meetingLink
    ) {}

    public function via($notifiable): array
    {
        // ✅ in-app + email
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $base = rtrim(config('app.url'), '/');
        $actionUrl = $base . '/app/interviews?focus=' . $this->interviewId;

        // Format datetime nicely in app timezone
        try {
            $dt = Carbon::parse($this->scheduledAtIso)->timezone(config('app.timezone', 'UTC'));
            $when = $dt->format('D, d M Y • h:i A');
        } catch (\Throwable $e) {
            $when = $this->scheduledAtIso;
        }

        $modeNice = ucfirst(str_replace(['_', '-'], ' ', $this->mode));

        $mail = (new MailMessage)
            ->subject("Interview Invitation — {$this->vacancyTitle}")
            ->greeting('Hello' . ($notifiable?->name ? " {$notifiable->name}" : '') . ',')
            ->line("You have been invited for an interview for **{$this->vacancyTitle}**.")
            ->line("**Date & Time:** {$when}")
            ->line("**Mode:** {$modeNice}");

        if (!empty($this->location)) {
            $mail->line("**Location:** {$this->location}");
        }

        if (!empty($this->meetingLink)) {
            $mail->line("**Meeting Link:** {$this->meetingLink}");
        }

        $mail->action('View & Respond', $actionUrl)
            ->line('Please log in and confirm or decline the interview from the Interviews page.');

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'interview_invite',
            'priority' => 'urgent',
            'title' => 'Interview Invitation',
            'message' => "You have been invited for an interview: {$this->vacancyTitle}",
            'url' => '/app/interviews?focus=' . $this->interviewId,
            'meta' => [
                'interview_id' => $this->interviewId,
                'vacancy_id' => $this->vacancyId,
                'scheduled_at' => $this->scheduledAtIso,
                'mode' => $this->mode,
                'location' => $this->location,
                'meeting_link' => $this->meetingLink,
            ],
        ];
    }
}
