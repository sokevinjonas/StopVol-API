<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Services\AuthService;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/request-otp",
     *     summary="Demander un code OTP",
     *     description="Envoie un code OTP au numéro de téléphone fourni",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP envoyé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Le numéro de téléphone est requis"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'envoi de l'OTP"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ], [
            'phone.required' => 'Le numéro de téléphone est requis',
        ]);
        try {
            $this->authService->requestOtp($request->phone);

            return response()->json([
                'message' => "OTP envoyé avec succès"
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => "Erreur lors de l'envoi de l'OTP",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     summary="Vérifier le code OTP",
     *     description="Vérifie le code OTP et retourne un token d'authentification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "otp"},
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="1|abcdef123456...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP invalide ou expiré"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|digits:6',
        ]);

        try {
            $data = $this->authService->verifyOtp($request->phone, $request->otp);

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
