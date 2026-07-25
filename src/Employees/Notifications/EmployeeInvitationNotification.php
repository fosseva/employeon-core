<?php

declare(strict_types=1);

namespace Employeon\Employees\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmployeeInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $employeeName,
        private readonly string $inviteUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to Employeon')
            ->greeting('Hello '.$this->employeeName)
            ->line('You have been invited to access the HRMS.')
            ->action('Accept invitation', $this->inviteUrl)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
