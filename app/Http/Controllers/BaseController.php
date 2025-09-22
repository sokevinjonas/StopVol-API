<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="StopVol API",
 *     version="1.0.0",
 *     description="API pour la déclaration de vol de véhicules au Burkina Faso",
 *     @OA\Contact(
 *         email="contact@stopvol.bf"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="StopVol API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format (Bearer <token>)"
 * )
 */
class BaseController extends Controller
{
    //
}
