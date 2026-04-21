<?php

namespace Vendor\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Vendor\User\Models\UserInvitation;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public UserInvitation $invitation) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $invitedBy  = $this->invitation->invitedBy?->name ?? config('app.name');
        $tenantName = $this->invitation->tenant?->name ?? config('app.name');
        $role       = config("user.tenant_roles.{$this->invitation->role_in_tenant}", $this->invitation->role_in_tenant);
        $acceptUrl  = route('users.accept', $this->invitation->token);
        $expiresDays = config('user.invitation.expire_days', 7);

        return (new MailMessage)
            ->subject("Invitation à rejoindre {$tenantName}")
            ->greeting("Bonjour !")
            ->line("{$invitedBy} vous invite à rejoindre **{$tenantName}** en tant que **{$role}**.")
            ->action('Accepter l\'invitation', $acceptUrl)
            ->line("Cette invitation expire dans {$expiresDays} jours.")
            ->line("Si vous n'avez pas demandé cette invitation, vous pouvez ignorer cet email.");
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'user_invitation',
            'email'       => $this->invitation->email,
            'invited_by'  => $this->invitation->invited_by,
            'role'        => $this->invitation->role_in_tenant,
            'expires_at'  => $this->invitation->expires_at,
        ];
    }
}