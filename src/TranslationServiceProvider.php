<?php

declare(strict_types=1);

namespace MinVWS\Laravel\Translation;

use Illuminate\Support\ServiceProvider;
use MinVWS\Laravel\Translation\Console\Commands\TranslationsCheckCommand;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
    }

    public function register(): void
    {
        $this->app->singleton('command.translations.check', function ($app) {
            return new TranslationsCheckCommand();
        });
    }
}
