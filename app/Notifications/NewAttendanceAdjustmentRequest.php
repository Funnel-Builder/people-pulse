<?php

namespace App\Notifications;

use App\Models\AttendanceAdjustmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewAttendanceAdjustmentRequest extends Notification
{
    use Queueable;

    public function __construct(public AttendanceAdjustmentRequest $adjustment)
    {
    }

    /**
     * Deliver via email and store in the database (for the in-app/browser bell).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    protected function dateLabel(): string
    {
        return Carbon::parse($this->adjustment->date)->format('M j, Y');
    }

    protected function timeLabel(): string
    {
        return Carbon::parse($this->adjustment->requested_time)->format('g:i A');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $adjustment = $this->adjustment;
        $typeLabel = $adjustment->typeLabel();

        return (new MailMessage)
            ->subject("New {$typeLabel} Request: {$adjustment->user->name} ({$this->dateLabel()})")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$adjustment->user->name} has submitted a {$typeLabel} request that needs your review.")
            ->line('Date: ' . $this->dateLabel())
            ->line(($adjustment->isLateEntry() ? 'Late arrival time: ' : 'Early departure time: ') . $this->timeLabel())
            ->line('Reason: ' . ($adjustment->reason ?: '—'))
            ->action('Review Request', url('/attendance/adjustments/approvals'))
            ->line('This is an automated notification from PeoplePulse.');
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("New {$this->adjustment->typeLabel()} request")
            ->icon('/favicons/android-chrome-192x192.png')
            ->badge('/favicons/favicon-32x32.png')
            ->body("{$this->adjustment->user->name} requested a {$this->adjustment->typeLabel()} ({$this->dateLabel()})")
            ->data(['url' => '/attendance/adjustments/approvals'])
            ->options(['TTL' => 86400]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_attendance_adjustment_request',
            'adjustment_id' => $this->adjustment->id,
            'employee_name' => $this->adjustment->user->name,
            'adjustment_type' => $this->adjustment->type,
            'date' => $this->dateLabel(),
            'title' => "New {$this->adjustment->typeLabel()} request",
            'message' => "{$this->adjustment->user->name} requested a {$this->adjustment->typeLabel()} ({$this->dateLabel()})",
            'url' => '/attendance/adjustments/approvals',
        ];
    }
}
