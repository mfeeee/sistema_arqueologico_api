<?php

namespace App\Providers;

use App\Models\ArtigoBemMaterial;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Policies\ArtigoBemMaterialPolicy;
use App\Policies\AuditoriaPolicy;
use App\Policies\BemMaterialPolicy;
use App\Policies\ColetaPolicy;
use App\Policies\CuradoriaPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Coleta::class => ColetaPolicy::class,
        BemMaterial::class => BemMaterialPolicy::class,
        Curadoria::class => CuradoriaPolicy::class,
        Auditoria::class => AuditoriaPolicy::class,
        ArtigoBemMaterial::class => ArtigoBemMaterialPolicy::class,
    ];

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePasswordReset();
        $this->registerPolicies();
        $this->configureRateLimiting();
    }

    /**
     * Define os limitadores de taxa da API.
     *
     * - public-api: endpoints de leitura sem autenticação obrigatória.
     *   Guests são limitados por IP; usuários autenticados têm limite maior por ID.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-api', function (Request $request): Limit {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });
    }

    protected function configurePasswordReset(): void
    {
        ResetPassword::createUrlUsing(function (mixed $user, string $token): string {
            $base = rtrim((string) config('app.password_reset_url', 'arqueopi://reset-password'), '/');

            return $base.'?token='.$token.'&email='.urlencode((string) $user->email);
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
