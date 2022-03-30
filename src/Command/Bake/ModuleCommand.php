<?php

namespace Modules\Command\Bake;

use Bake\Command\BakeCommand;
use Bake\Utility\Process;
use Bake\Utility\TemplateRenderer;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * Class ModuleCommand
 * @package Modules\Command\Bake
 */
class ModuleCommand extends BakeCommand
{

    /**
     * Module name
     *
     * @var string
     */
    protected $module;

    /**
     * Module bake template
     *
     * @var string
     */
    protected $template;

    /**
     * Module base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * Module full path
     *
     * @var string
     */
    protected $fullPath;


    /**
     * Module full source path
     *
     * @var string
     */
    protected $fullSrcPath;

    /**
     * Base module namespace
     *
     * @var string
     */
    protected $baseNamespace;

    /**
     * Root composer file path
     *
     * @var string
     */
    protected $rootComposerFilePath;

    /**
     * Initialize module bake command
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->basePath = ROOT . DS . 'modules' . DS;
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath);
        }
        $this->template = 'Modules.module';
        $this->baseNamespace = 'Modules\BaseModule';
        $this->rootComposerFilePath = ROOT . DS . 'composer.json';
    }

    /**
     * Execute module bake command
     *
     * @param Arguments $args The console arguments
     * @param ConsoleIo $io The console io
     * @return int Execute status code
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->extractCommonProperties($args);
        $name = $args->getArgumentAt(0);
        if (empty($name)) {
            $io->error('You must provide a module name.');
            $io->error('To make an "Example" module, run `bin/cake bake module Example`.');
            return static::CODE_ERROR;
        }
        $name = str_replace('\\', '/', $name);
        if (preg_match('/[\'^£$%&*()}{@#~?<>,|=_+¬-]/', $name)) {
            $io->error('Module name is invalid');
            return static::CODE_ERROR;
        }
        $this->module = implode('/', array_map([Inflector::class, 'camelize'], explode('/', $name)));
        $this->fullPath = $this->basePath . $name . DS;
        $this->fullSrcPath = $this->basePath . $name . DS . 'src' . DS;
        if (is_dir($this->fullPath)) {
            $io->out(sprintf('Module %s already exists', $this->module));
            $io->out(sprintf('Path: %s', $this->fullPath));
            return static::CODE_ERROR;
        }
        if (!$this->bake($args, $io)) {
            $io->error(sprintf('An error occurred trying to bake module: %s', $this->module));
            $io->error(sprintf('Path: %s', $this->fullPath));
            return static::CODE_ERROR;
        }
        $this->bakeTest($args, $io);
        return static::CODE_SUCCESS;
    }

    /**
     * Generate a class stub
     *
     * @param Arguments $args The console arguments
     * @param ConsoleIo $io The console io
     * @return bool Bake status
     */
    protected function bake(Arguments $args, ConsoleIo $io): bool
    {
        $io->out(sprintf('<info>Module Name:</info> %s', $this->module));
        $io->out(sprintf('<info>Module Directory:</info> %s', $this->fullPath));
        $io->hr();
        $looksGood = $io->askChoice('Looks okay?', ['y', 'n', 'q'], 'y');
        if (strtolower($looksGood) !== 'y') {
            $this->abort();
            return false;
        }
        if (!is_dir($this->fullSrcPath)) {
            $io->out(sprintf('<info>Creating folder</info> `%s` ...', $this->fullSrcPath));
            mkdir($this->fullSrcPath, 0777, true);
        }
        if (!$this->generateTemplate($args, $io)) {
            return false;
        }
        if (!$this->modifyAutoloader($args, $io)) {
            return false;
        }
        $io->hr();
        return true;
    }

    /**
     * Generate bake template
     *
     * @param Arguments $args The console arguments
     * @param ConsoleIo $io The console io
     * @return bool Generate template status
     */
    protected function generateTemplate(Arguments $args, ConsoleIo $io): bool
    {
        $className = basename($this->fullPath);
        $filePath = $this->fullSrcPath . $className . 'Module.php';
        $renderer = new TemplateRenderer();
        $renderer->set('module', $this->module);
        $renderer->set('namespace', $this->namespace());
        $renderer->set('className', $className);
        $renderer->set('baseNamespace', $this->baseNamespace);
        $contents = $renderer->generate($this->template);
        $io->out(sprintf('<info>Creating file</info> `%s` ...', $filePath));
        $io->createFile($filePath, $contents, (bool)$args->getOption('force'));
        $io->out(sprintf('<success>Created file</success> `%s`', $filePath));
        return true;
    }

    /**
     * Modify composer autoloader
     *
     * @param Arguments $args The console arguments
     * @param ConsoleIo $io The console io
     * @return bool Modify root composer.json file status
     */
    protected function modifyAutoloader(Arguments $args, ConsoleIo $io): bool
    {
        $module = $this->module;
        $filePath = $this->rootComposerFilePath;
        $autoloadPath = str_replace(ROOT . DS, '', $this->fullPath);
        if (!file_exists($filePath)) {
            $io->out(sprintf('<error>Project composer file not found:</error> `%s`', $filePath));
            return false;
        }
        $config = json_decode(file_get_contents($filePath), true);
        $namespace = str_replace('/', '\\', $module);
        $config['autoload']['psr-4']['Modules\\' . $namespace . '\\'] = $autoloadPath . 'src/';
        $config['autoload-dev']['psr-4']['Modules\\' . $namespace . '\\Test\\'] = $autoloadPath . 'tests/';
        $composer = $this->findComposer($args, $io);
        if (!$composer) {
            $io->out('<error>Could not locate composer executable.</error> Add composer to your PATH, or use the --composer option.');
            $this->abort();
        }
        $io->out(sprintf('<info>Modifying project composer file</info> `%s` ...', $filePath));
        $out = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $io->createFile($filePath, $out, (bool)$args->getOption('force'));
        try {
            $cwd = getcwd();
            chdir(dirname($this->rootComposerFilePath));
            $command = 'php ' . escapeshellarg($composer) . ' dump-autoload';
            $process = new Process($io);
            $io->out('<info>Executing</info> `composer dump-autoload` ...');
            $io->out($process->call($command));
            chdir($cwd);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            $io->error(sprintf('Could not execute `composer dump-autoload`: %s', $error));
            $this->abort();
        }
        return true;
    }


    /**
     * Generate a test case.
     *
     * @param Arguments $args The console arguments
     * @param ConsoleIo $io The console io
     * @return void
     */
    public function bakeTest(Arguments $args, ConsoleIo $io): void
    {
        if ($args->getOption('no-test')) {
            return;
        }
        //ToDo: Add bake tests logic
    }


    /**
     * Get module namespace
     *
     * @return string Module namespace
     */
    protected function namespace()
    {
        $module = str_replace('/', '\\', $this->module);
        return 'Modules\\' . $module;
    }


    /**
     * Uses either the CLI option or looks in $PATH and cwd for composer.
     *
     * @param Arguments $args The command arguments.
     * @param ConsoleIo $io The console io
     * @return string|bool Either the path to composer or false if it cannot be found.
     */
    protected function findComposer(Arguments $args, ConsoleIo $io)
    {
        if ($args->hasOption('composer')) {
            /** @var string $path */
            $path = $args->getOption('composer');
            if (file_exists($path)) {
                return $path;
            }
        }
        $composer = false;
        $path = env('PATH');
        if (!empty($path)) {
            $paths = explode(PATH_SEPARATOR, $path);
            $composer = $this->searchPath($paths, $io);
        }
        return $composer;
    }

    /**
     * Search the $PATH for composer.
     *
     * @param array $path The paths to search.
     * @param ConsoleIo $io The console io
     * @return string|bool Either the path to composer or false if it cannot be found.
     */
    protected function searchPath(array $path, ConsoleIo $io)
    {
        $composer = ['composer.phar', 'composer'];
        foreach ($path as $dir) {
            foreach ($composer as $cmd) {
                if (is_file($dir . DS . $cmd)) {
                    return $dir . DS . $cmd;
                }
            }
        }
        return false;
    }
}
