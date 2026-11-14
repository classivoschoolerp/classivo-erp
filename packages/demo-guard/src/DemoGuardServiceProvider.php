<?php

namespace Classivo\DemoGuard;

use Classivo\DemoGuard\Http\Middleware\LimitDemoUploads;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DemoGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/demo-guard.php', 'demo-guard');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'demo-guard');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'demo-guard');

        if (! DemoGuard::enabled()) {
            return;
        }

        $this->guardModels();
        $this->app['router']->pushMiddlewareToGroup('web', LimitDemoUploads::class);

        if (config('demo-guard.banner.enabled', true)) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('demo-guard::banner', ['resetTime' => config('demo-guard.banner.reset_time')])->render(),
            );
        }
    }

    private function guardModels(): void
    {
        $userModel = config('demo-guard.users.model') ?: config('auth.providers.users.model');
        if ($userModel && class_exists($userModel)) {
            $protected = (array) config('demo-guard.users.protected_attributes', []);
            $userModel::updating(function (Model $m) use ($protected) {
                if (DemoGuard::active() && $m->isDirty($protected)) {
                    DemoGuard::deny(__('demo-guard::messages.user_locked'));
                }
            });
            if (config('demo-guard.users.block_delete', true)) {
                $userModel::deleting(fn () => DemoGuard::active() ? DemoGuard::deny(__('demo-guard::messages.user_locked')) : null);
            }
        }

        foreach ((array) config('demo-guard.role_models', []) as $class) {
            if (! class_exists($class)) {
                continue;
            }
            $class::updating(fn () => DemoGuard::active() ? DemoGuard::deny(__('demo-guard::messages.roles_locked')) : null);
            $class::deleting(fn () => DemoGuard::active() ? DemoGuard::deny(__('demo-guard::messages.roles_locked')) : null);
        }

        foreach ((array) config('demo-guard.locked_models', []) as $class) {
            if (! class_exists($class)) {
                continue;
            }
            foreach (['creating', 'updating', 'deleting'] as $event) {
                $class::$event(fn () => DemoGuard::active() ? DemoGuard::deny(__('demo-guard::messages.setting_locked')) : null);
            }
        }
    }
}
