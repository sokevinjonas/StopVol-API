<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Modèle utilisateur",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Identifiant unique de l'utilisateur"),
 *     @OA\Property(property="phone", type="string", example="+22670123456", description="Numéro de téléphone"),
 *     @OA\Property(property="name", type="string", example="Jean Dupont", description="Nom complet"),
 *     @OA\Property(property="photo", type="string", nullable=true, example="photos/user123.jpg", description="Chemin vers la photo de profil"),
 *     @OA\Property(property="document_type", type="string", enum={"cnib", "permis_conduire", "passport"}, nullable=true, example="cnib", description="Type de document d'identité"),
 *     @OA\Property(property="document_number", type="string", nullable=true, example="B123456789", description="Numéro du document"),
 *     @OA\Property(property="document_front", type="string", nullable=true, example="documents/front123.jpg", description="Photo recto du document"),
 *     @OA\Property(property="document_back", type="string", nullable=true, example="documents/back123.jpg", description="Photo verso du document"),
 *     @OA\Property(property="city", type="string", nullable=true, example="Ouagadougou", description="Ville"),
 *     @OA\Property(property="district", type="string", nullable=true, example="Secteur 15", description="District/Secteur"),
 *     @OA\Property(property="entity_id", type="string", format="uuid", nullable=true, description="ID de l'entité (commissariat/gendarmerie)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de mise à jour")
 * )
 * 
 * @OA\Schema(
 *     schema="Declaration",
 *     type="object",
 *     title="Declaration",
 *     description="Modèle déclaration de vol",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Identifiant unique de la déclaration"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID de l'utilisateur déclarant"),
 *     @OA\Property(property="plate_number", type="string", example="11-BF-1234", description="Numéro de plaque du véhicule"),
 *     @OA\Property(property="chassis_number", type="string", nullable=true, example="VF1234567890", description="Numéro de châssis"),
 *     @OA\Property(property="card_number", type="string", nullable=true, example="CG123456", description="Numéro de carte grise"),
 *     @OA\Property(property="brand", type="string", nullable=true, example="Toyota", description="Marque du véhicule"),
 *     @OA\Property(property="model", type="string", nullable=true, example="Corolla", description="Modèle du véhicule"),
 *     @OA\Property(property="color", type="string", nullable=true, example="Blanc", description="Couleur du véhicule"),
 *     @OA\Property(property="pictures", type="array", nullable=true, @OA\Items(type="string"), example={"photo1.jpg", "photo2.jpg"}, description="Photos du véhicule"),
 *     @OA\Property(property="theft_date", type="string", format="date", example="2024-01-15", description="Date du vol"),
 *     @OA\Property(property="theft_location", type="string", example="Ouagadougou, Secteur 15", description="Lieu du vol"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending", description="Statut de la déclaration"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de mise à jour"),
 *     @OA\Property(property="user", ref="#/components/schemas/User", description="Informations de l'utilisateur déclarant"),
 *     @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/DeclarationImage"), description="Images associées à la déclaration")
 * )
 * 
 * @OA\Schema(
 *     schema="DeclarationImage",
 *     type="object",
 *     title="DeclarationImage",
 *     description="Image associée à une déclaration",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002", description="Identifiant unique de l'image"),
 *     @OA\Property(property="declaration_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="ID de la déclaration"),
 *     @OA\Property(property="document_type", type="string", enum={"cnib", "permis_conduire", "passport", "photo"}, example="cnib", description="Type de document"),
 *     @OA\Property(property="type", type="string", enum={"card_front", "card_back"}, nullable=true, example="card_front", description="Face du document"),
 *     @OA\Property(property="path", type="string", example="declaration_images/image123.jpg", description="Chemin vers l'image"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date de mise à jour")
 * )
 */
class Schemas
{
    // Cette classe ne contient que les annotations de schémas
}
