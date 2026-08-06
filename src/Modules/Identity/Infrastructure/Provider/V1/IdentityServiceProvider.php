<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Provider\V1;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use LogicException;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Application\Port\Out\Identity\IUserIdGenerator;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Application\Port\Out\Transaction\ITransactionManager;
use Modules\Identity\Infrastructure\Adapter\Out\Authentication\EloquentRefreshTokenService;
use Modules\Identity\Infrastructure\Adapter\Out\Authentication\LcobucciAccessTokenService;
use Modules\Identity\Infrastructure\Adapter\Out\Authentication\SystemClock;
use Modules\Identity\Infrastructure\Adapter\Out\Identity\LaravelUserIdGenerator;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentUserRepository;
use Modules\Identity\Infrastructure\Adapter\Out\Security\LaravelPasswordHasher;
use Modules\Identity\Infrastructure\Adapter\Out\Transaction\LaravelTransactionManager;
use Modules\Identity\Infrastructure\Console\GenerateJwtKeysCommand;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            IAccessTokenService::class,
            function (Application $application): IAccessTokenService {
                $config = $application->make(ConfigRepository::class);
                $issuer = $config->get(
                    'identity.authentication.jwt.issuer',
                );
                $audience = $config->get(
                    'identity.authentication.jwt.audience',
                );
                $ttlSeconds = $config->get(
                    'identity.authentication.jwt.access_ttl_seconds',
                );
                $privateKeyPath = $config->get(
                    'identity.authentication.jwt.private_key_path',
                );
                $publicKeyPath = $config->get(
                    'identity.authentication.jwt.public_key_path',
                );
                $privateKeyPassphrase = $config->get(
                    'identity.authentication.jwt.private_key_passphrase',
                );

                if (
                    ! is_string($issuer) || $issuer === ''
                    || ! is_string($audience) || $audience === ''
                    || ! is_int($ttlSeconds)
                    || ! is_string($privateKeyPath) || $privateKeyPath === ''
                    || ! is_string($publicKeyPath) || $publicKeyPath === ''
                    || ! is_string($privateKeyPassphrase)
                ) {
                    throw new LogicException('Identity JWT configuration is invalid.');
                }

                return new LcobucciAccessTokenService(
                    configuration: Configuration::forAsymmetricSigner(
                        signer: new Sha256,
                        signingKey: InMemory::file(
                            $privateKeyPath,
                            $privateKeyPassphrase,
                        ),
                        verificationKey: InMemory::file($publicKeyPath),
                    ),
                    clock: new SystemClock,
                    issuer: $issuer,
                    audience: $audience,
                    ttlSeconds: $ttlSeconds,
                );
            },
        );

        $this->app->singleton(
            IRefreshTokenService::class,
            function (Application $application): IRefreshTokenService {
                $ttlSeconds = $application
                    ->make(ConfigRepository::class)
                    ->get(
                        'identity.authentication.jwt.refresh_ttl_seconds',
                    );

                if (! is_int($ttlSeconds)) {
                    throw new LogicException(
                        'Identity refresh token configuration is invalid.',
                    );
                }

                return new EloquentRefreshTokenService(
                    connection: $application->make(ConnectionInterface::class),
                    clock: new SystemClock,
                    ttlSeconds: $ttlSeconds,
                );
            },
        );

        $this->app->bind(
            IUserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            IPasswordHasher::class,
            LaravelPasswordHasher::class,
        );

        $this->app->bind(
            IUserIdGenerator::class,
            LaravelUserIdGenerator::class,
        );

        $this->app->bind(
            ITransactionManager::class,
            LaravelTransactionManager::class,
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([GenerateJwtKeysCommand::class]);
        }

        $this->registerRateLimiters();
        $this->registerRoutes();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for(
            'identity-register',
            fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()),
        );

        RateLimiter::for(
            'identity-login',
            fn (Request $request): Limit => Limit::perMinute(5)->by(
                Str::lower($request->string('email')->toString()).'|'.$request->ip(),
            ),
        );

        RateLimiter::for(
            'identity-refresh',
            fn (Request $request): Limit => Limit::perMinute(30)->by(
                hash('sha256', $request->string('refresh_token')->toString()).'|'.$request->ip(),
            ),
        );
    }

    private function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                Route::prefix('identity')
                    ->name('identity.')
                    ->group(function (): void {
                        $this->loadRoutesFrom(dirname(__DIR__, 3).'/Presentation/Http/V1/Routes/auth_routes.php');
                        $this->loadRoutesFrom(dirname(__DIR__, 3).'/Presentation/Http/V1/Routes/user_routes.php');
                    });
            });
    }
}
