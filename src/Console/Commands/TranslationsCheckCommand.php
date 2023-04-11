<?php

declare(strict_types=1);

namespace MinVWS\Laravel\Translation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use MinVWS\Laravel\Translation\Services\TranslationService;
use Symfony\Component\Finder\Finder;

/*
 * This is a crude parser that will check our sources for unused or untranslated strings. It is a very rudimentary
 * check, but still good enough to give a good enough result.
 */

class TranslationsCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:check {--langpath=} {--update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks code and views for unused/missing translations.';

    protected TranslationService $translationService;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $langPath = $this->option('langpath') ?? App::basePath('resources/lang');

        $this->translationService = new TranslationService(
            strval($langPath),
            [
                App::basePath('app'),
                App::basePath('tests'),
                App::basePath('resources/views'),
                App::basePath('resources/js'),
                App::basePath('config'),
            ]
        );

        $this->warn("Note: false positives can occur due to incorrect scanning of quotes and dynamic messages\n");

        $unusedTranslations = $this->translationService->getUnusedTranslations();
        $this->info("Unused translated strings:");
        print json_encode($unusedTranslations, JSON_PRETTY_PRINT);
        print "\n";
        print "\n";

        $this->info("Untranslated strings:");
        $untranslatedTranslations = $this->translationService->getUntranslatedTranslations();
        print json_encode($untranslatedTranslations, JSON_PRETTY_PRINT);
        print "\n";
        print "\n";

        $this->info("Unfinished strings:");
        $unfinished = $this->translationService->getUnfinishedTranslations();
        print json_encode($unfinished, JSON_PRETTY_PRINT);
        print "\n";
        print "\n";

        if ($this->option('update')) {
            $this->info("Updating translations...");
            $this->translationService->updateTranslations();
        }

        return 0;
    }
}
