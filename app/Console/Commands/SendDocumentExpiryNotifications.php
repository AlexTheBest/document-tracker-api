<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DocumentExpiryNotification;
use Illuminate\Console\Command;

class SendDocumentExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:send-expiry-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily notifications to users about expiring and expired documents';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending document expiry notifications...');

        $users = User::with([
            'documents' => function ($query) {
                // Only get non-archived documents that are either expiring soon or expired
                $query->whereNull('archived_at')
                    ->where(function ($q) {
                        $q->whereBetween('expires_at', [now(), now()->addDays(7)])
                            ->orWhere('expires_at', '<', now());
                    });
            }
        ])->get();

        $notificationsSent = 0;

        foreach ($users as $user) {
            // Skip users with no expiring or expired documents
            if ($user->documents->isEmpty()) {
                continue;
            }

            // Separate documents into expiring soon and expired
            $expiringSoon = $user->documents->filter(function ($document) {
                return $document->isExpiringSoon();
            });

            $expired = $user->documents->filter(function ($document) {
                return $document->isExpired();
            });

            // Send notification if there are any documents to report
            if ($expiringSoon->isNotEmpty() || $expired->isNotEmpty()) {
                $user->notify(new DocumentExpiryNotification($expiringSoon, $expired));
                $notificationsSent++;
                
                $this->info("Sent notification to {$user->email} ({$expiringSoon->count()} expiring soon, {$expired->count()} expired)");
            }
        }

        $this->info("Sent {$notificationsSent} notification(s) successfully.");

        return Command::SUCCESS;
    }
}
