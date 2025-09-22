<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Helpers\OtpHelper;
use App\Services\NotificationService;

class AuthService 
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Demande OTP
     */
    public function requestOtp($phone)
    {
        // Créer ou récupérer l'utilisateur
        $user = User::firstOrCreate(['phone_number' => $phone], [
            'role' => 'citizen',
        ]);

        // Générer OTP
        $otp = OtpHelper::generateOtp($phone);

        // Stocker l'OTP dans la colonne users.otp_code
        $user->otp_code = $otp;
        $user->save();

        // Envoyer OTP via NotificationService (AQILAS)
        $this->notificationService->send(
            $user->id,
            "OTP StopVol",
            "Votre code OTP est : $otp"
        );

        return $otp; // optionnel
    }

    /**
     * Vérifier OTP et générer token
     */
    public function verifyOtp($phone, $otp)
    {
        $user = User::where('phone_number', $phone)->firstOrFail();

        if ($user->otp_code !== $otp) {
            throw new Exception("OTP invalide ou expiré");
        }

        // Reset OTP après validation
        $user->otp_code = null;
        $user->save();

        // Générer token avec Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
