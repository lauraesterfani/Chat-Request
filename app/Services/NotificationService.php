<?php

namespace App\Services;

use App\Jobs\SendNotificationEmail;
use App\Models\NotificationPreference;
use App\Models\NotificationRecord;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function forStudent(User $student, string $category, string $title, string $body, ?RequestModel $request = null, ?string $eventKey = null): ?NotificationRecord
    {
        return $this->create('student', (string) $student->getKey(), $student->email, $category, $title, $body, $request, $eventKey);
    }

    public function forStaff(StaffAdmin $staff, string $category, string $title, string $body, ?RequestModel $request = null, ?string $eventKey = null): ?NotificationRecord
    {
        return $this->create('staff_admin', (string) $staff->getKey(), $staff->email, $category, $title, $body, $request, $eventKey);
    }

    private function create(string $type, string $id, ?string $email, string $category, string $title, string $body, ?RequestModel $request, ?string $eventKey): ?NotificationRecord
    {
        $key = $eventKey ?? implode(':', [$type, $id, $category, $request?->id ?? 'general', sha1($title.$body)]);
        $preference = NotificationPreference::firstOrCreate(
            ['recipient_type'=>$type,'recipient_id'=>$id,'category'=>$category],
            ['internal_enabled'=>true,'email_enabled'=>false]
        );
        $preference->internal_enabled = (bool) ($preference->internal_enabled ?? true);
        $preference->email_enabled = (bool) ($preference->email_enabled ?? false);
        if (!$preference->internal_enabled && !$preference->email_enabled) return null;
        return DB::transaction(function () use ($type, $id, $category, $title, $body, $request, $key, $preference) {
            $notification = NotificationRecord::firstOrCreate(['idempotency_key'=>$key], [
                'recipient_type'=>$type, 'recipient_id'=>$id, 'request_id'=>$request?->id, 'category'=>$category,
                'title'=>$title, 'body'=>$body, 'link'=>$request ? '/requests/acesso/'.$request->id : null,
                'channel'=>'internal', 'email_status'=>$preference->email_enabled ? 'pending' : 'not_requested',
            ]);
            if ($notification->wasRecentlyCreated && $preference->email_enabled) SendNotificationEmail::dispatch($notification->id)->afterCommit();
            return $notification;
        });
    }
}
