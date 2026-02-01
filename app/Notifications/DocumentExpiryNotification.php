<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Collection $expiringSoonDocuments,
        public Collection $expiredDocuments
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Document Expiry Reminder')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is your daily document expiry reminder.');

        // Add expiring soon documents
        if ($this->expiringSoonDocuments->isNotEmpty()) {
            $message->line('**Documents expiring within the next 7 days:**');
            
            foreach ($this->expiringSoonDocuments as $document) {
                $daysUntilExpiry = now()->diffInDays($document->expires_at, false);
                $message->line('- ' . $document->name . ' (expires in ' . ceil($daysUntilExpiry) . ' days on ' . $document->expires_at->format('M d, Y') . ')');
            }

            $message->line('');
        }

        // Add expired documents
        if ($this->expiredDocuments->isNotEmpty()) {
            $message->line('**Documents that have expired and need attention:**');
            
            foreach ($this->expiredDocuments as $document) {
                $daysExpired = now()->diffInDays($document->expires_at);
                $message->line('- ' . $document->name . ' (expired ' . $daysExpired . ' days ago on ' . $document->expires_at->format('M d, Y') . ')');
            }

            $message->line('');
        }

        if ($this->expiringSoonDocuments->isEmpty() && $this->expiredDocuments->isEmpty()) {
            $message->line('You have no documents expiring soon or expired. Great job staying on top of things!');
        } else {
            $message->line('Please review these documents and take appropriate action.');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'expiring_soon_count' => $this->expiringSoonDocuments->count(),
            'expired_count' => $this->expiredDocuments->count(),
        ];
    }
}
