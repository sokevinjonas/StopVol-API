<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\User\Services\UserService;
use App\Domains\OTP\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private UserService $userService,
        private OtpService $otpService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/auth/send-otp",
     *     tags={"Authentication"},
     *     summary="Envoyer un code OTP",
     *     description="Envoie un code OTP par SMS au numéro de téléphone fourni",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone au format international")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="phone", type="string", example="+22670123456"),
     *                 @OA\Property(property="expires_in_minutes", type="integer", example=10),
     *                 @OA\Property(property="can_resend_in", type="integer", example=60)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de tentatives",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Trop de demandes OTP. Veuillez patienter.")
     *         )
     *     )
     * )
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => 'required|string|min:8|max:15'
            ]);

            $phone = $request->phone;

            // Check if user can request new OTP
            if (!$this->otpService->canRequestNewOtp($phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de demandes OTP. Veuillez patienter.',
                    'data' => $this->otpService->getOtpStats($phone)
                ], 429);
            }

            // Send OTP
            $otpCode = $this->otpService->sendOtp($phone);

            return response()->json([
                'success' => true,
                'message' => 'Code OTP envoyé avec succès',
                'data' => [
                    'phone' => $phone,
                    'expires_in_minutes' => 10,
                    'can_resend_in' => $this->otpService->getRemainingTime($phone)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code OTP'
            ], 500);
        }
    }

    /**
     * Verify OTP and authenticate user
     */
    /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     tags={"Authentication"},
     *     summary="Vérifier le code OTP",
     *     description="Vérifie le code OTP et retourne un token d'authentification",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "code"},
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone"),
     *             @OA\Property(property="code", type="string", example="123456", description="Code OTP reçu par SMS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Authentification réussie"),
     *             @OA\Property(property="token", type="string", example="1|abcdef123456..."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+22670123456"),
     *                 @OA\Property(property="role", type="string", example="citizen"),
     *                 @OA\Property(property="profile_status", type="string", example="incomplete")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'code' => 'required|string|size:6'
            ]);

            $phone = $request->phone;
            $code = $request->code;

            // Verify OTP
            if (!$this->otpService->verifyOtp($phone, $code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP invalide ou expiré'
                ], 401);
            }

            // Find or create user
            $user = $this->userService->findByPhone($phone);
            
            if (!$user) {
                // Create new user
                $user = $this->userService->createUser([
                    'phone' => $phone,
                    'phone_verified_at' => now()
                ]);
            } else {
                // Update phone verification
                $user = $this->userService->updateUser($user, [
                    'phone_verified_at' => now()
                ]);
            }

            // Create token
            $token = $user->createToken('StopVol API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Authentification réussie',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone' => $user->phone,
                        'name' => $user->name,
                        'role' => $user->role,
                        'profile_status' => $user->profile_status,
                        'profile_complete' => $user->isProfileComplete()
                    ],
                    'token' => $token
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du code OTP'
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    /**
     * @OA\Post(
     *     path="/api/auth/resend-otp",
     *     tags={"Authentication"},
     *     summary="Renvoyer un code OTP",
     *     description="Renvoie un nouveau code OTP au numéro de téléphone",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nouveau code OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nouveau code OTP envoyé"),
     *             @OA\Property(property="expires_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de tentatives",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Veuillez attendre avant de demander un nouveau code")
     *         )
     *     )
     * )
     */
    public function resendOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string'
            ]);

            $phone = $request->phone;

            // Resend OTP
            $otpCode = $this->otpService->resendOtp($phone);

            return response()->json([
                'success' => true,
                'message' => 'Code OTP renvoyé avec succès',
                'data' => [
                    'phone' => $phone,
                    'expires_in_minutes' => 10
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi du code OTP'
            ], 500);
        }
    }

    /**
     * Get current user info
     */
    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     description="Retourne les informations de l'utilisateur actuellement authentifié",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+22670123456"),
     *                 @OA\Property(property="role", type="string", example="citizen"),
     *                 @OA\Property(property="profile_status", type="string", example="validated"),
     *                 @OA\Property(property="city", type="string", example="Ouagadougou"),
     *                 @OA\Property(property="district", type="string", example="Secteur 15"),
     *                 @OA\Property(property="entity_id", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
                'role' => $user->role,
                'profile_status' => $user->profile_status,
                'profile_complete' => $user->isProfileComplete(),
                'can_create_declaration' => $user->canCreateDeclaration(),
                'city' => $user->city,
                'district' => $user->district,
                'entity_id' => $user->entity_id
            ]
        ]);
    }

    /**
     * Logout user
     */
    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Se déconnecter",
     *     description="Révoque le token d'authentification actuel",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }
}
