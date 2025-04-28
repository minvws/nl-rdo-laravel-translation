<?php

declare(strict_types=1);

namespace MinVWS\Laravel\Translation\Tests\Unit\Services;

use MinVWS\Laravel\Translation\Services\TranslationService;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use ReflectionClass;
use ReflectionException;

class TranslationServiceTest extends TestCase
{
    private vfsStreamDirectory $root;
    private TranslationService $service;
    private string $langPath;
    private array $sourcePaths;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a virtual filesystem
        $this->root = vfsStream::setup('root');
        $this->langPath = vfsStream::url('root/lang');
        $this->sourcePaths = [vfsStream::url('root/source')];

        // Create test directories
        mkdir($this->langPath);
        mkdir($this->sourcePaths[0]);

        $this->service = new TranslationService($this->langPath, $this->sourcePaths);
    }

    public function testGetUnusedTranslations(): void
    {
        // Create test translation files
        file_put_contents(
            $this->langPath . '/en.json',
            json_encode(['greeting' => 'Hello', 'unused' => 'Unused'])
        );

        // Create a test source file
        file_put_contents(
            $this->sourcePaths[0] . '/test.php',
            '<?php echo __("greeting");'
        );

        $unused = $this->service->getUnusedTranslations();
        $this->assertContains('unused', $unused);
        $this->assertNotContains('greeting', $unused);
    }

    public function testGetUntranslatedTranslations(): void
    {
        // Create a test translation file
        file_put_contents(
            $this->langPath . '/en.json',
            json_encode(['greeting' => 'Hello'])
        );

        // Create a test source file with an untranslated key
        file_put_contents(
            $this->sourcePaths[0] . '/test.php',
            '<?php echo __("greeting"); echo __("missing");'
        );

        $untranslated = $this->service->getUntranslatedTranslations();
        $this->assertContains('missing', $untranslated);
        $this->assertNotContains('greeting', $untranslated);
    }

    public function testGetUnfinishedTranslations(): void
    {
        // Create test translation files with different languages
        file_put_contents(
            $this->langPath . '/en.json',
            json_encode(['greeting' => 'Hello', 'farewell' => 'Goodbye'])
        );
        file_put_contents(
            $this->langPath . '/nl.json',
            json_encode(['greeting' => 'Hallo']) // Missing 'farewell'
        );

        $unfinished = $this->service->getUnfinishedTranslations();
        $this->assertArrayHasKey('farewell', $unfinished);
        $this->assertArrayNotHasKey('greeting', $unfinished);
    }

    public function testUpdateTranslations(): void
    {
        // Create an initial translation file
        file_put_contents(
            $this->langPath . '/en.json',
            json_encode(['existing' => 'Value'])
        );

        // Create a source file with a new translation
        file_put_contents(
            $this->sourcePaths[0] . '/test.php',
            '<?php echo __("new_key");'
        );

        $this->service->updateTranslations();

        $updatedContent = json_decode(file_get_contents($this->langPath . '/en.json'), true);
        $this->assertArrayHasKey('new_key', $updatedContent);
        $this->assertEquals('__EN__new_key', $updatedContent['new_key']);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchTranslationKeysFromMultipleFiles(): void
    {
        file_put_contents(
            $this->langPath . '/en.json',
            json_encode(['key1' => 'Value 1', 'key2' => 'Value 2'])
        );
        file_put_contents(
            $this->langPath . '/nl.json',
            json_encode(['key1' => 'Waarde 1', 'key3' => 'Waarde 3'])
        );

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchTranslationKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $this->langPath);

        $this->assertArrayHasKey('all', $result);
        $this->assertContains('key1', $result['all']);
        $this->assertContains('key2', $result['all']);
        $this->assertContains('key3', $result['all']);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchTranslationsFromSource(): void
    {
        // Create test source files with different translation patterns
        file_put_contents(
            $this->sourcePaths[0] . '/test1.php',
            '<?php echo __("key1"); echo trans("key2");'
        );
        file_put_contents(
            $this->sourcePaths[0] . '/test2.js',
            'console.log(__("key3")); alert(trans("key4"));'
        );

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('fetchTranslationsFromSource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $this->sourcePaths);

        $this->assertContains('key1', $result);
        $this->assertContains('key2', $result);
        $this->assertContains('key3', $result);
        $this->assertContains('key4', $result);
    }
}
