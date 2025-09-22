<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class AuthService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Générer et stocker OTP
     */
    public function requestOtp($phone)
    {
        $code = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Stocker OTP dans la table otp_codes, peu importe l'envoi
        OtpCode::create([
            'id' => Str::uuid(),
            'phone' => $phone,
            'code' => $code,
            'used' => false,
            'expires_at' => $expiresAt,
        ]);

        // Envoyer OTP via AQILAS
        try {
            $this->notificationService->sendSms($phone, "Votre OTP StopVol est : $code");
        } catch (Exception $e) {
            // Même si l'envoi échoue, l'OTP est stocké
        }

        return $code;
    }

    /**
     * Vérifier OTP et connecter/créer l'utilisateur
     */
    public function verifyOtp($phone, $code)
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otp) {
            throw new Exception("OTP invalide ou expiré");
        }

        // Marquer OTP comme utilisé
        $otp->update(['used' => true]);

        // Créer ou récupérer l'utilisateur
        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['role' => 'citizen']
        );

        // Générer token API
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
