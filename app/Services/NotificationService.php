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
    public function send($userId, $title, $message)
    {
        $notif = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            // Récupérer le numéro de téléphone de l'utilisateur
            $user = $notif->user;

            // Préparer la requête HTTP vers AQILAS
            $url = "https://api.aqilas.com/send";
            $data = [
                'api_key' => $this->apiKey,
                'sender_id' => $this->senderId,
                'phone' => $user->phone_number,
                'message' => $message
            ];

            // Envoi via cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            // Vérifier la réponse et mettre à jour le status
            $result = json_decode($response);
            if (isset($result->success) && $result->success) {
                $notif->update(['status' => 'sent']);
            } else {
                $notif->update(['status' => 'failed']);
            }

        } catch (Exception $e) {
            $notif->update(['status' => 'failed']);
            Log::error("Erreur AQILAS: ".$e->getMessage());
        }

        return $notif;
    }
}
