<?php

namespace App\Notifications;

use App\Models\AttendanceAdjustmentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceAdjustmentProcessed extends Notification
{
    use Queueable;

    /**
     * @param string      $outcome 'approved' | 'rejected' | 'step_approved'
     * @param string|null $byRole  approver_type of the step just approved (for 'step_approved')
     */
    public function __construct(
        public AttendanceAdjustmentRequest $adjustment,
        public string $outcome,
        public User $approver,
        public ?string $comment = null,
        public ?string $byRole = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    protected function isApproved(): bool
    {
        return $this->outcome === 'approved';
    }

    protected function isStep(): bool
    {
        return $this->outcome === 'step_approved';
    }

    protected function dateLabel(): string
    {
        return Carbon::parse($this->adjustment->date)->format('M j, Y');
    }

    protected function byLabel(): string
    {
        return match ($this->byRole) {
            'cover_person' => 'the cover person',
            'manager' => 'your manager',
            'admin' => 'the admin',
            default => 'an approver',
        };
    }

    protected function shortBody(): string
    {
        $type = $this->adjustment->typeLabel();

        if ($this->isStep()) {
            return "Your {$type} request for {$this->dateLabel()} was approved by {$this->byLabel()} and is awaiting the next approval.";
        }

        $verb = $this->isApproved() ? 'approved' : 'rejected';

        return "Your {$type} request for {$this->dateLabel()} was {$verb}.";
    }

    protected function titleText(): string
    {
        if ($this->isStep()) {
            return "{$this->adjustment->typeLabel()} request approved by {$this->byLabel()}";
        }

        return "{$this->adjustment->typeLabel()} request {$this->statusWord()}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = $this->adjustment->typeLabel();
        $mail = (new MailMessage)
            ->subject("{$typeLabel} Request {$this->statusWord()}: {$this->dateLabel()}")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->shortBody());

        if ($this->isApproved()) {
            $mail->line('The late arrival / early departure for this day has been excused.');
        } elseif ($this->comment) {
            $mail->line('Reason: ' . $this->comment);
        }

        return $mail
            ->action('View My Attendance', url('/attendance'))
            ->line('This is an automated notification from PeoplePulse.');
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->titleText())
            ->icon('/favicons/android-chrome-192x192.png')
            ->badge('/favicons/favicon-32x32.png')
            ->body($this->shortBody())
            ->data(['url' => '/attendance'])
            ->options(['TTL' => 86400]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'attendance_adjustment_processed',
            'adjustment_id' => $this->adjustment->id,
            'adjustment_type' => $this->adjustment->type,
            'outcome' => $this->outcome,
            'date' => $this->dateLabel(),
            'title' => $this->titleText(),
            'message' => $this->shortBody(),
            'url' => '/attendance',
        ];
    }

    protected function statusWord(): string
    {
        return $this->isApproved() ? 'Approved' : 'Rejected';
    }
}
