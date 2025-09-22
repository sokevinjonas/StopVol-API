<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Demande OTP
     * POST /api/auth/request-otp
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        try {
            $this->authService->requestOtp($request->phone_number);

            return response()->json([
                'message' => 'OTP envoyé avec succès'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi de l\'OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérification OTP
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp' => 'required|digits:6',
        ]);

        try {
            $data = $this->authService->verifyOtp($request->phone_number, $request->otp);

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => $data['user'],
                'token' => $data['token']
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'OTP invalide ou expiré',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
