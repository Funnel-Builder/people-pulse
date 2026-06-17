<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewLeaveRequest extends Notification
{
    use Queueable;

    public function __construct(public Leave $leave)
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

    /**
     * Human-readable date range for the leave.
     */
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

    public function toMail(object $notifiable): MailMessage
    {
        $leave = $this->leave;
        $typeLabel = $leave->type === Leave::TYPE_ADVANCE ? 'Advance Leave' : 'Post Leave';

        return (new MailMessage)
            ->subject("New Leave Request: {$leave->user->name} ({$this->dateRange()})")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$leave->user->name} has submitted a new {$typeLabel} request that needs your attention.")
            ->line('Leave Type: ' . ($leave->leaveType->name ?? 'N/A'))
            ->line('Dates: ' . $this->dateRange())
            ->line('Reason: ' . ($leave->reason ?: '—'))
            ->action('Review Request', url('/leaves/' . $leave->id))
            ->line('This is an automated notification from PeoplePulse.');
    }

    /**
     * Native browser/OS push notification (delivered even when the tab is closed).
     */
    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New leave request')
            ->icon('/favicons/android-chrome-192x192.png')
            ->badge('/favicons/favicon-32x32.png')
            ->body("{$this->leave->user->name} requested leave ({$this->dateRange()})")
            ->data(['url' => '/leaves/' . $this->leave->id])
            ->options(['TTL' => 86400]);
    }

    /**
     * Stored in the `notifications` table and surfaced in the in-app/browser bell.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_leave_request',
            'leave_id' => $this->leave->id,
            'employee_name' => $this->leave->user->name,
            'leave_type' => $this->leave->type,
            'date_range' => $this->dateRange(),
            'title' => 'New leave request',
            'message' => "{$this->leave->user->name} requested leave ({$this->dateRange()})",
            'url' => '/leaves/' . $this->leave->id,
        ];
    }
}
