<?php

declare(strict_types=1);

namespace MinVWS\Laravel\Translation\Services;

use Illuminate\Support\Facades\App;
use Symfony\Component\Finder\Finder;

class TranslationService
{
    protected string $languagePath;
    protected array $sourcePaths;

    protected bool $processed = false;
    protected array $transKeys = [];
    protected array $foundKeys = [];

    public function __construct(string $languagePath, ?array $sourcePaths = null)
    {
        $this->languagePath = $languagePath;

        if ($sourcePaths === null) {
            $this->sourcePaths = [
                App::basePath("app"),
                App::basePath("tests"),
                App::basePath("resources/views"),
                App::basePath("resources/js"),
                App::basePath("config"),
            ];
        } else {
            $this->sourcePaths = $sourcePaths;
        }
    }

    public function getUnusedTranslations(): array
    {
        $this->retrieveTranslations();

        return array_diff($this->transKeys['all'], $this->foundKeys);
    }

    public function getUntranslatedTranslations(): array
    {
        $this->retrieveTranslations();

        return array_diff($this->foundKeys, $this->transKeys['all']);
    }

    public function getUnfinishedTranslations(): array
    {
        $this->retrieveTranslations();

        return $this->calculateUnfinished($this->transKeys);
    }

    public function updateTranslations(): void
    {
        $this->retrieveTranslations();

        $finder = new Finder();
        /** @phpstan-ignore-next-line */
        $files = $finder->files()->name("*.json")->in(App::langPath());

        foreach ($files as $file) {
            $locale = strtoupper($file->getBasename(".json"));

            $f = file_get_contents($file->getPathname());
            if (!$f) {
                continue;
            }

            $missingData = [];
            foreach ($this->getUntranslatedTranslations() as $v) {
                $missingData[$v] = "__" . $locale . "__" . $v;
            }

            $data = json_decode($f, true);
            $data = array_merge($data, $missingData);

            file_put_contents($file->getPathname(), json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    protected function retrieveTranslations(): void
    {
        if ($this->processed) {
            return;
        }

        $this->transKeys = $this->fetchTranslationKeys($this->languagePath);
        $this->foundKeys = $this->fetchTranslationsFromSource($this->sourcePaths);
        $this->processed = true;
    }


    protected function calculateUnfinished(array $keys): array
    {
        $max = 0;
        $ret = [];
        foreach (array_keys($keys) as $k) {
            if ($k == "all") {
                continue;
            }

            foreach ($keys[$k] as $label) {
                if (!isset($ret[$label])) {
                    $ret[$label] = 0;
                }
                $ret[$label]++;

                if ($ret[$label] > $max) {
                    $max = $ret[$label];
                }
            }
        }

        // Remove all items not equal to the max
        return array_filter($ret, function ($v) use ($max) {
            return $v < $max;
        });
    }


    protected function fetchTranslationKeys(string $path): array
    {
        $finder = new Finder();
        $files = $finder->files()->name("*.json")->in($path);

        $transKeys = [];
        foreach ($files as $file) {
            $locale = $file->getBasename(".json");

            $f = file_get_contents($file->getPathname());
            if (!$f) {
                continue;
            }

            $keys = json_decode($f, true);

            $transKeys['all'] = array_merge($transKeys['all'] ?? [], array_keys($keys));
            $transKeys[$locale] = array_merge($transKeys[$locale] ?? [], array_keys($keys));
        }

        // Remove duplicates
        foreach ($transKeys as $k => $v) {
            $transKeys[$k] = array_unique($v);
        }

        return $transKeys;
    }

    protected function fetchTranslationsFromSource(array $paths): array
    {
        $finder = new Finder();
        $files = $finder->files()
            ->name("*.php")
            ->name("*.js")
        ;
        foreach ($paths as $path) {
            $files = $files->in($path);
        }

        $foundTrans = [];
        foreach ($files as $file) {
            $data = file_get_contents($file->getPathName());
            if (!$data) {
                continue;
            }

            if (preg_match_all('/(?:__|trans|\@lang)\(\n?\s*(["\'])(.+)\1\n?\s*\)/U', $data, $matches)) {
                foreach ($matches[2] as $match) {
                    $foundTrans[] = $match;
                }
            }
        }

        return array_unique($foundTrans);
    }
}
