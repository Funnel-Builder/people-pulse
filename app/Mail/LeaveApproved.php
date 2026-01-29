<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\UserLeaveBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LeaveApproved extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $leaveBalances;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Leave $leave
    ) {
        // Get all leave balances for this user
        $this->leaveBalances = UserLeaveBalance::with('leaveType')
            ->where('user_id', $leave->user_id)
            ->get();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $leaveDates = $this->leave->dates->pluck('date')->sort();
        $startDate = $leaveDates->first();
        $endDate = $leaveDates->last();

        $dateRange = $startDate === $endDate
            ? \Illuminate\Support\Carbon::parse($startDate)->format('M j, Y')
            : \Illuminate\Support\Carbon::parse($startDate)->format('M j') . ' - ' . \Illuminate\Support\Carbon::parse($endDate)->format('M j, Y');

        return new Envelope(
            subject: 'Leave Approved: ' . $dateRange,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-approved',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
