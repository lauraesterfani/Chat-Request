<?php

namespace App\Jobs;

use App\Models\NotificationPreference;
use App\Models\NotificationRecord;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\User;
use App\Services\RequestAccessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public string $notificationId) {}

    public function handle(): void
    {
        $notification = NotificationRecord::find($this->notificationId);
        if (! $notification || $notification->email_status !== 'pending') {
            return;
        }
        $recipient = $notification->recipient_type === 'student' ? User::find($notification->recipient_id) : StaffAdmin::find($notification->recipient_id);
        $preference = NotificationPreference::where([
            'recipient_type' => $notification->recipient_type,
            'recipient_id' => $notification->recipient_id,
            'category' => $notification->category,
        ])->first();
        $requestAccessRevoked = false;
        if ($notification->request_id) {
            if ($recipient instanceof User || $recipient instanceof StaffAdmin) {
                $requestModel = RequestModel::find($notification->request_id);
                $requestAccessRevoked = ! $requestModel
                    || ! app(RequestAccessService::class)->canView($recipient, $requestModel);
            } else {
                $requestAccessRevoked = true;
            }
        }
        if (! $recipient?->email || ! filled($recipient->email_verified_at ?? null)
            || ($recipient instanceof StaffAdmin && ! $recipient->isActive())
            || ! $preference?->email_enabled || $requestAccessRevoked) {
            $notification->update(['email_status' => 'skipped']);

            return;
        }
        Mail::raw($notification->body."\n\nAcesse o Chat Request para continuar.", function ($message) use ($recipient, $notification) {
            $message->to($recipient->email)->subject($notification->title);
        });
        $notification->update(['email_status' => 'sent']);
    }

    public function failed(): void
    {
        NotificationRecord::whereKey($this->notificationId)->update(['email_status' => 'failed']);
    }
}
