# StopVol API

Backend du projet **StopVol**, une application de déclaration et de suivi des vols d’engins (motos, engins à deux roues, potentiellement extensible aux voitures et autres objets).  
Développé avec **Laravel 9+**, avec une architecture sécurisée, API RESTful et authentification OTP.

---

## Table des matières

- [Contexte](#contexte)  
- [Technologies](#technologies)  
- [Architecture Backend](#architecture-backend)  
- [Base de données](#base-de-données)  
- [Authentification](#authentification)  
- [Endpoints API](#endpoints-api)  
- [Logique des utilisateurs](#logique-des-utilisateurs)  
- [Notifications](#notifications)  
- [Stockage des fichiers](#stockage-des-fichiers)  
- [Sécurité & bonnes pratiques](#sécurité--bonnes-pratiques)

---

## Contexte

**StopVol** permet aux citoyens de déclarer leurs engins volés facilement via mobile.  
Le backend centralise les déclarations et permet aux admins (commissariats/gendarmerie) de rechercher des plaques ou numéros de châssis et de notifier le propriétaire lorsqu’un engin est retrouvé.  

### Objectifs
- Simplifier la déclaration de vol pour les citoyens.  
- Fournir un outil rapide et fiable pour les forces de l’ordre.  
- Garantir la sécurité et la crédibilité des déclarations.

---

## Technologies

- **Backend** : Laravel 9+  
- **Base de données** : MySQL/PostgreSQL (UUID pour toutes les tables principales)  
- **Authentification** : OTP via SMS (Laravel Sanctum pour token API)  
- **Stockage fichiers** : Storage local ou cloud (photos, documents officiels)  
- **Notifications** : SMS & push (FCM) via queue jobs  

---

## Architecture Backend

- **Models** : User, Entity, Declaration, DeclarationImage, Notification, OtpCode  
- **Controllers** : AuthController, ProfileController, DeclarationController, AdminController  
- **Middlewares** : 
  - `auth:sanctum` → sécurise les routes API  
  - `can:admin-access` → limite certaines actions aux admins  
  - `profile.validated` → protège la création de déclarations  
- **Queue / Jobs** : envoi SMS et notifications push  

---

## Base de données

Toutes les tables principales utilisent **UUID** comme identifiant.

### Tables principales

#### Entities
- `id` (UUID, PK)  
- `name`, `address`, `phone`, `manager_name`  
- `timestamps`  

#### Users
- `id` (UUID, PK)  
- `name`, `phone` (unique)  
- `role` : `citizen` | `entity_admin`  
- `entity_id` (FK, UUID)  
- `profile_picture`, `id_card_front`, `id_card_back`  
- `id_type` : `cnib`, `permis`, `passport`  
- `city`, `district`  
- `profile_status` : `incomplete`, `pending_validation`, `validated`  
- `phone_verified_at`  
- `remember_token`  
- `timestamps`  

#### OTP Codes
- `id` (UUID, PK)  
- `phone`, `code`  
- `used` (bool), `expires_at`  
- `timestamps`  

#### Declarations
- `id` (UUID, PK)  
- `user_id` (FK)  
- `plate_number`, `chassis_number`, `card_number`, `brand`, `model`, `color`  
- `pictures` (JSON)  
- `theft_date`, `theft_location`  
- `status` : `pending`, `found`, `closed`  
- `timestamps`  

#### Declaration Images
- `id` (UUID, PK)  
- `declaration_id` (FK)  
- `document_type` : `cnib`, `permis_conduire`, `passport`, `photo`  
- `type` : `card_front`, `card_back` (nullable)  
- `path` (chemin fichier)  
- `timestamps`  

#### Notifications
- `id` (UUID, PK)  
- `declaration_id` (FK)  
- `admin_id` (FK, nullable)  
- `message`, `channel` : `sms` | `app`  
- `sent_at`  
- `timestamps`  

---

## Authentification

- **Entrée via téléphone** → OTP envoyé par SMS  
- **Vérification OTP** → création / récupération du compte utilisateur  
- **Token API** via **Laravel Sanctum**  
- **Flow** :
  1. Entrez numéro de téléphone  
  2. Réception et saisie OTP  
  3. Accès au tableau de bord  
  4. Profil complet obligatoire avant déclaration  

---

## Endpoints API

### Auth
- `POST /auth/send-otp` → envoie OTP  
- `POST /auth/verify-otp` → vérifie OTP et retourne token  

### Profile
- `GET /me` → info utilisateur  
- `POST /profile/complete` → compléter profil (photo + documents + ville/quartier)  

### Declarations (citizen)
- `POST /declarations` → créer déclaration  
- `GET /declarations` → liste déclarations  
- `GET /declarations/{id}` → détail d’une déclaration  

### Admin (entity_admin)
- `GET /admin/declarations` → liste toutes les déclarations  
- `GET /admin/declarations/search?plate=XXX` → recherche par plaque  
- `POST /admin/declarations/{id}/notify` → notifier le propriétaire  
- `POST /admin/declarations/{id}/status` → mettre à jour statut  

---

## Logique des utilisateurs

- **Citizen** : peut déclarer un engin volé, voir l’historique, recevoir notifications.  
- **Admin** : peut consulter toutes les déclarations, rechercher par plaque, notifier, mettre à jour le statut.  
- **Profil obligatoire pour déclarer** : photo + document officiel + ville + quartier.  
- **OTP obligatoire à chaque connexion** pour sécurité et simplicité (pas de mot de passe).  

---

## Notifications

- Envoi via **SMS** et/ou **Push** (Firebase Cloud Messaging)  
- Utilisation de **Jobs/Queues** pour traitement asynchrone  
- Statut et date d’envoi stockés dans `notifications`  

---

## Stockage des fichiers

- Stockage dans `storage/app/public/stopvol/{user_id}/`  
- Limites : 5 MB max / type MIME autorisé : jpeg/png/pdf  
- Images de documents : recto/verso pour CNIB, permis, passeport  
- Photos d’engin : `document_type = photo`, `type = null`  

---

## Sécurité & bonnes pratiques

- Rate limiting sur OTP (ex: 3 OTP / heure)  
- Vérification des fichiers uploadés  
- UUID pour toutes les tables principales → plus sécurisé et scalable  
- Logging : recherche plaque, notifications, actions sensibles  
- Rétention : suppression automatique possible après X mois pour déclarations clôturées  
- Middleware `auth:sanctum` et `can:admin-access`  
- Validation serveur stricte pour toutes les entrées  

---

## Notes

- Le backend est prévu pour être **scalable** et **sécurisé**.  
- Peut évoluer pour gérer d’autres types d’objets volés (voitures, biens, personnes).  
- Facilement intégrable avec une app mobile **Ionic** ou tout front RESTful.  

---

**Auteur / Mainteneur** : Jonas – [StopVol Project]  
