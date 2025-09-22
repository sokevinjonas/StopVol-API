<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Domain Repositories
use App\Domains\User\Repositories\UserRepository;
use App\Domains\Declaration\Repositories\DeclarationRepository;
use App\Domains\Notification\Repositories\NotificationRepository;
use App\Domains\OTP\Repositories\OtpRepository;

// Infrastructure Implementations
use App\Infrastructure\Persistence\Eloquent\UserRepositoryEloquent;
use App\Infrastructure\Persistence\Eloquent\DeclarationRepositoryEloquent;
use App\Infrastructure\Persistence\Eloquent\NotificationRepositoryEloquent;
use App\Infrastructure\Persistence\Eloquent\OtpRepositoryEloquent;

// Domain Services
use App\Domains\User\Services\UserService;
use App\Domains\Declaration\Services\DeclarationService;
use App\Domains\Notification\Services\NotificationService;
use App\Domains\OTP\Services\OtpService;

// Infrastructure Services
use App\Infrastructure\Messaging\SmsSender;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Eloquent Implementations
        $this->app->bind(UserRepository::class, UserRepositoryEloquent::class);
        $this->app->bind(DeclarationRepository::class, DeclarationRepositoryEloquent::class);
        $this->app->bind(NotificationRepository::class, NotificationRepositoryEloquent::class);
        $this->app->bind(OtpRepository::class, OtpRepositoryEloquent::class);

        // Register Domain Services as Singletons
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserRepository::class)
            );
        });

        $this->app->singleton(DeclarationService::class, function ($app) {
            return new DeclarationService(
                $app->make(DeclarationRepository::class)
            );
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationRepository::class)
            );
        });

        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService(
                $app->make(OtpRepository::class),
                $app->make(SmsSender::class)
            );
        });

        // Register Infrastructure Services
        $this->app->singleton(SmsSender::class, function ($app) {
            return new SmsSender();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            UserRepository::class,
            DeclarationRepository::class,
            NotificationRepository::class,
            OtpRepository::class,
            UserService::class,
            DeclarationService::class,
            NotificationService::class,
            OtpService::class,
            SmsSender::class,
        ];
    }
}
