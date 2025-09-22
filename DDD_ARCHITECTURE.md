# Architecture Domain-Driven Design (DDD) - StopVol API

## Vue d'ensemble

Ce projet a été restructuré selon les principes du Domain-Driven Design (DDD) pour améliorer la maintenabilité, la testabilité et la séparation des responsabilités.

## Structure des dossiers

```
app/
├── Domains/                    # Couche Domaine
│   ├── User/                   # Domaine Utilisateur
│   │   ├── Entities/           # Entités métier
│   │   ├── Repositories/       # Interfaces des repositories
│   │   ├── Services/           # Services métier
│   │   └── Events/             # Événements du domaine
│   ├── Declaration/            # Domaine Déclaration
│   │   ├── Entities/
│   │   ├── ValueObjects/       # Objets de valeur
│   │   ├── Repositories/
│   │   ├── Services/
│   │   └── Events/
│   ├── Notification/           # Domaine Notification
│   │   ├── Entities/
│   │   ├── Repositories/
│   │   ├── Services/
│   │   └── Events/
│   └── OTP/                    # Domaine OTP
│       ├── Entities/
│       ├── Repositories/
│       └── Services/
├── Infrastructure/             # Couche Infrastructure
│   ├── Persistence/
│   │   └── Eloquent/          # Implémentations Eloquent
│   └── Messaging/             # Services de messagerie
├── Jobs/                      # Jobs asynchrones
└── Providers/                 # Service Providers
```

## Domaines

### 1. User Domain
**Responsabilités :**
- Gestion des utilisateurs (citoyens et admins)
- Validation et complétion des profils
- Upload des documents d'identité
- Gestion des rôles et permissions

**Entités principales :**
- `User` : Entité utilisateur avec logique métier

**Services :**
- `UserService` : Logique métier pour les utilisateurs

### 2. Declaration Domain
**Responsabilités :**
- Création et gestion des déclarations de vol
- Recherche par plaque, châssis, carte grise
- Gestion des statuts (pending, found, closed)
- Upload des photos d'engins

**Entités principales :**
- `Declaration` : Entité déclaration avec logique métier

**Value Objects :**
- `PlateNumber` : Validation des numéros de plaque

**Services :**
- `DeclarationService` : Logique métier pour les déclarations

### 3. Notification Domain
**Responsabilités :**
- Envoi de notifications SMS et push
- Gestion des canaux de notification
- Suivi des notifications envoyées

**Entités principales :**
- `Notification` : Entité notification avec logique métier

**Services :**
- `NotificationService` : Logique métier pour les notifications

### 4. OTP Domain
**Responsabilités :**
- Génération et validation des codes OTP
- Gestion de l'expiration
- Rate limiting des demandes

**Entités principales :**
- `OtpCode` : Entité OTP avec logique de validation

**Services :**
- `OtpService` : Logique métier pour les OTP

## Couche Infrastructure

### Persistence
- **Eloquent Repositories** : Implémentations concrètes des interfaces de repository
- Séparation entre la logique métier et l'accès aux données

### Messaging
- **SmsSender** : Service d'envoi de SMS avec support multi-provider
- Support pour Twilio, Nexmo, Africa's Talking

## Jobs Asynchrones

- **SendSmsNotification** : Envoi asynchrone de SMS
- **SendPushNotification** : Envoi asynchrone de notifications push

## Avantages de cette architecture

### 1. Séparation des responsabilités
- Chaque domaine gère sa propre logique métier
- Infrastructure séparée de la logique métier
- Facilite les tests unitaires

### 2. Maintenabilité
- Code organisé par domaine métier
- Réduction du couplage entre les composants
- Évolution facilitée

### 3. Testabilité
- Interfaces permettent le mocking facile
- Logique métier isolée
- Tests unitaires plus simples

### 4. Extensibilité
- Ajout de nouveaux domaines facilité
- Changement d'infrastructure sans impact sur la logique métier
- Support multi-provider pour les services externes

## Utilisation

### Injection de dépendances

```php
// Dans un contrôleur
public function __construct(
    private UserService $userService,
    private DeclarationService $declarationService,
    private OtpService $otpService
) {}
```

### Utilisation des services

```php
// Création d'un utilisateur
$user = $this->userService->createUser($data);

// Envoi d'un OTP
$otp = $this->otpService->sendOtp($phone);

// Création d'une déclaration
$declaration = $this->declarationService->createDeclaration($userId, $data);
```

### Événements du domaine

```php
// Les événements sont automatiquement déclenchés
// Exemple : UserProfileCompleted, DeclarationCreated, NotificationSent
```

## Configuration

### Service Provider
Le `DomainServiceProvider` lie les interfaces aux implémentations :

```php
$this->app->bind(UserRepository::class, UserRepositoryEloquent::class);
```

### Configuration SMS
Dans `config/services.php` :

```php
'sms' => [
    'provider' => env('SMS_PROVIDER', 'log'),
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],
    // Autres providers...
],
```

## Migration depuis l'ancienne architecture

1. Les anciens modèles Eloquent sont remplacés par les entités du domaine
2. La logique métier est déplacée vers les services du domaine
3. Les contrôleurs utilisent maintenant les services du domaine
4. Les repositories abstraient l'accès aux données

## Bonnes pratiques

1. **Entités** : Contiennent la logique métier et les règles de validation
2. **Services** : Orchestrent les opérations complexes
3. **Repositories** : Abstraient l'accès aux données
4. **Value Objects** : Encapsulent les valeurs avec validation
5. **Events** : Découplent les actions et permettent l'extensibilité

Cette architecture respecte les principes SOLID et facilite la maintenance et l'évolution du projet StopVol.
