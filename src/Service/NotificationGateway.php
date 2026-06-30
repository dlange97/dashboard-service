<?php

declare(strict_types=1);

namespace App\Service;

class NotificationGateway
{
    public function __construct(
        private readonly string $notificationServiceUrl,
        private readonly string $internalNotificationToken,
    ) {
    }

    public function notifyResourceShared(
        string $resourceType,
        string $resourceName,
        string $recipientUserId,
        string $sharedByUserId,
    ): bool {
        $baseUrl = rtrim(trim($this->notificationServiceUrl), '/');
        if ($baseUrl === '' || $this->internalNotificationToken === '') {
            return false;
        }

        try {
            $payload = json_encode([
                'resourceType' => $resourceType,
                'resourceName' => $resourceName,
                'recipientUserId' => $recipientUserId,
                'sharedBy' => [
                    'userId' => $sharedByUserId,
                ],
            ], JSON_THROW_ON_ERROR);

            $ch = curl_init($baseUrl . '/notification/internal/resource-shared');
            if ($ch === false) {
                return false;
            }

            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Internal-Token: ' . $this->internalNotificationToken,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
            ]);

            curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $statusCode >= 200 && $statusCode < 300;
        } catch (\Throwable) {
            return false;
        }
    }
}
