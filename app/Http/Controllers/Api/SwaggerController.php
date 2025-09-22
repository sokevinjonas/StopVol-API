<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="StopVol API",
 *     version="1.0.0",
 *     description="API pour la déclaration et le suivi des vols d'engins (motos, véhicules, etc.)",
 *     @OA\Contact(
 *         email="contact@stopvol.com",
 *         name="StopVol Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="StopVol API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Utilisez le token Bearer obtenu après authentification OTP"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints d'authentification via OTP"
 * )
 * 
 * @OA\Tag(
 *     name="Profile",
 *     description="Gestion du profil utilisateur"
 * )
 * 
 * @OA\Tag(
 *     name="Declarations",
 *     description="Gestion des déclarations de vol"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints administrateur (commissariats/gendarmerie)"
 * )
 * 
 * @OA\Tag(
 *     name="System",
 *     description="Endpoints système (santé, documentation)"
 * )
 */
class SwaggerController extends Controller
{
    //
}
