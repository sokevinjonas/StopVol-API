<?php

namespace App\Services;

use Log;
use Exception;
use App\Models\Notification;

class NotificationService
{
    protected $apiKey;
    protected $senderId;

    public function __construct()
    {
        $this->apiKey = env('AQILAS_API_KEY');
        $this->senderId = env('AQILAS_SENDER_ID');
    }

    /**
     * Envoie une notification via AQILAS
     */
    public function sendSms($phone, $message)
    {
        try {
            $payload = [
                'from' => $this->senderId,
                'to' => [$phone],
                'text' => $message,
            ];

            $ch = curl_init("https://www.aqilas.com/api/v1/sms");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "X-AUTH-TOKEN: {$this->apiKey}"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response);

            if (isset($result->success) && $result->success === true) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }
    }

}
