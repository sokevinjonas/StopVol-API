<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class OtpHelper
{
    /**
     * Génère un OTP numérique de 6 chiffres
     */
    public static function generateOtp($phone)
    {
        // Génère un code aléatoire entre 100000 et 999999
        $otp = random_int(100000, 999999);

        // Stocke OTP dans le cache 5 minutes
        Cache::put("otp_$phone", $otp, now()->addMinutes(5));

        return $otp;
    }

    public static function verifyOtp($phone, $otp)
    {
        $cachedOtp = Cache::get("otp_$phone");
        return $cachedOtp && $cachedOtp == $otp;
    }
}
