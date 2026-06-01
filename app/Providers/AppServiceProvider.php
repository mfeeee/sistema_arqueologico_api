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
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
