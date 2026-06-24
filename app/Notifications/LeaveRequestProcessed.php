<?php

namespace App\Notifications;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class LeaveRequestProcessed extends Notification
{
    use Queueable;

    /**
     * @param string      $outcome 'approved' | 'rejected' | 'step_approved'
     * @param string|null $byRole  approver_type of the step just approved (for 'step_approved')
     */
    public function __construct(
        public Leave $leave,
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
        return ['database', WebPushChannel::class];
    }

    protected function isApproved(): bool
    {
        return $this->outcome === 'approved';
    }

    protected function isStep(): bool
    {
        return $this->outcome === 'step_approved';
    }

    protected function statusWord(): string
    {
        return $this->outcome === 'rejected' ? 'Rejected' : 'Approved';
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

    protected function titleText(): string
    {
        if ($this->isStep()) {
            return "Leave request approved by {$this->byLabel()}";
        }

        return "Leave request {$this->statusWord()}";
    }

    protected function dateRange(): string
    {
        $dates = $this->leave->dates->pluck('date')->sort();
        $start = $dates->first();
        $end = $dates->last();

        if (!$start) {
            return '';
        }

        return $start === $end
            ? Carbon::parse($start)->format('M j, Y')
            : Carbon::parse($start)->format('M j') . ' - ' . Carbon::parse($end)->format('M j, Y');
    }

    protected function shortBody(): string
    {
        $type = $this->leave->leaveType->name ?? 'Leave';

        if ($this->isStep()) {
            return "Your {$type} request ({$this->dateRange()}) was approved by {$this->byLabel()} and is awaiting the next approval.";
        }

        $verb = $this->isApproved() ? 'approved' : 'rejected';

        return "Your {$type} request ({$this->dateRange()}) was {$verb}.";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->leave->leaveType->name ?? 'Leave';
        $mail = (new MailMessage)
            ->subject("Leave Request {$this->statusWord()}: {$type} ({$this->dateRange()})")
            ->greeting("Hi {$notifiable->name},")
            ->line($this->shortBody());

        if (!$this->isApproved() && $this->comment) {
            $mail->line('Reason: ' . $this->comment);
        }

        return $mail
            ->action('View My Leaves', url('/leaves/' . $this->leave->id))
            ->line('This is an automated notification from PeoplePulse.');
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->titleText())
            ->icon('/favicons/android-chrome-192x192.png')
            ->badge('/favicons/favicon-32x32.png')
            ->body($this->shortBody())
            ->data(['url' => '/leaves/' . $this->leave->id])
            ->options(['TTL' => 86400]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_request_processed',
            'leave_id' => $this->leave->id,
            'leave_type' => $this->leave->leaveType->name ?? null,
            'outcome' => $this->outcome,
            'date_range' => $this->dateRange(),
            'title' => $this->titleText(),
            'message' => $this->shortBody(),
            'url' => '/leaves/' . $this->leave->id,
        ];
    }
}
