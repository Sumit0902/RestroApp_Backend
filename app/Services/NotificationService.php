<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function createNotification($message, $notifierId, $recipientId,$companyId)
    {
        return Notification::create([
            'message' => $message,
            'notifier_id' => $notifierId,
            'recipient_id' => $recipientId,
            'company_id' => $companyId,
            'is_read' => false,
        ]);
    }
}
