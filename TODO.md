# Configuration Swagger pour StopVol API

## Étapes à compléter :

- [x] 1. Installation du package Swagger
  - [x] Installer darkaonline/l5-swagger via Composer
  - [x] Publier la configuration

- [x] 2. Configuration Swagger
  - [x] Configurer le fichier config/l5-swagger.php
  - [x] Définir les informations de base de l'API

- [x] 3. Annotations Swagger dans les contrôleurs
  - [x] Ajouter les annotations OpenAPI dans AuthController
  - [x] Ajouter les annotations OpenAPI dans ProfileController
  - [x] Ajouter les annotations OpenAPI dans DeclarationController
  - [x] Créer les schémas de données (User, Declaration, DeclarationImage)

- [x] 4. Configuration de la sécurité
  - [x] Configurer l'authentification Bearer Token (Sanctum)
  - [x] Définir les schémas de sécurité

- [x] 5. Génération et test
  - [x] Générer la documentation
  - [ ] Test de l'interface Swagger UI

## Progression :
✅ Configuration terminée ! La documentation Swagger est maintenant disponible à l'adresse : `/api/documentation`

## Fichiers créés/modifiés :
- ✅ config/l5-swagger.php (configuration personnalisée)
- ✅ app/Http/Controllers/BaseController.php (annotations de base)
- ✅ app/Http/Controllers/AuthController.php (annotations endpoints auth)
- ✅ app/Http/Controllers/ProfileController.php (annotations endpoints profil)
- ✅ app/Http/Controllers/DeclarationController.php (annotations endpoints déclarations)
- ✅ app/Http/Controllers/Schemas.php (schémas de données)
- ✅ routes/api.php (correction des routes)
- ✅ storage/api-docs/api-docs.json (documentation générée)
