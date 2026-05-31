<?php

namespace App\Providers;

use App\Mail\CourierTransport;
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
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
        $this->registerMailTransports();
    }

    protected function registerMailTransports(): void
    {
        Mail::extend('courier', function (array $config = []) {
            return new CourierTransport($config['api_key'] ?? '');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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
