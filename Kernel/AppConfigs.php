<?php

declare(strict_types=1);

namespace MaplePHP\Foundation\Kernel;

use MaplePHP\Container\Interfaces\ContainerInterface;
use Whoops\Handler\HandlerInterface;

class AppConfigs
{
    // Defualt configs
    public const CONFIG_FILES = [
        "database",
        "providers",
        "routers"
    ];


    protected $dir;
    protected $attr = array();
    protected $container;
    protected $routerFiles;
    protected $exclRouterFiles = array();
    protected $hasTempEngine = true;
    protected $hasDBEngine = true;
    protected $errorHandler;
    protected $whoopsHandler;

    /**
     * Get global ENV
     * @param  string      $key
     * @param  string|null $fallback Specify  possible fallback
     * @return mixed
     */
    protected function getenv(string $key, string $fallback = null)
    {
        return ($this->attr[$key] ?? $fallback);
    }

    /**
     * Get config data
     * @param  string $key
     * @return mixed
     */
    protected function getConfig(string $key)
    {
        return ($this->attr['config'][$key] ?? null);
    }

    /**
     * Get Directory path from config file (IF starts with )
     * @param string  $dirPath  If dir path starts with slash then it is absolute else its
     *                          relative from the Root folder
     */
    protected function getConfigDir(string $dirPath): string
    {
        if (substr($dirPath, 0, 1) === "/") {
            return $dirPath;
        }
        return realpath($this->dir->getRoot($dirPath));
    }

    /**
     * Build the config data array
     * @return array
     */
    protected function getConfigFileData(): array
    {
        $data = $this->requireConfigFile("app");
        if(empty($data['configs']) || !is_array($data['configs'])) {
            $data['configs'] = self::CONFIG_FILES;
        }

        foreach($data['configs'] as $file) {
            $data += $this->requireConfigFile($file);
        }

        return ["config" => $data];
    }

    /**
     * Requires whitelisted config files
     * @param  string $file filename without ending
     * @return array
     */
    final protected function requireConfigFile(string $file): array
    {
        $expectedPath = $this->dir->getRoot("config/{$file}.php");
        $fullFilePath = realpath($expectedPath);
        if(!$fullFilePath || !is_file($fullFilePath)) {
            throw new \Exception("Could not find the \"{$expectedPath}\" in the \"config\" directory.", 1);
        }
        $data = require_once($fullFilePath);
        if (!is_array($data)) {
            throw new \Exception("The config file named \"{$file}\" has to return an array!", 1);
        }
        return $data;
    }

    /**
     * Whoops handler ben set
     * @param  string  $class
     * @return boolean
     */
    protected function hasWhoopsHandler(string $class): bool
    {
        return (isset($this->whoopsHandler[$class]));
    }

    /**
     * Nice error reporting
     * @psalm-suppress InvalidReturnStatement
     * @param  string $class handler
     * @return object
     */
    protected function getWhoopsHandler(string $class): object
    {
        if (!$this->hasWhoopsHandler($class)) {
            $this->whoopsHandler[$class] = new $class();
        }
        return $this->whoopsHandler[$class];
    }

    /**
     * Add some custom configs from other places that the .env and config files
     * @param array $attr
     */
    public function setConfigs(array $attr): void
    {
        $this->attr = $attr;
    }

    /**
     * Set container
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Overwrite config and specify router file
     * @param array $routerFiles
     */
    public function setRouterFiles(array $routerFiles): void
    {
        $this->routerFiles = $routerFiles;
    }

    /**
     * Overwrite config and specify router file
     * @param array $routerFiles
     */
    public function excludeRouterFiles(array $routerFiles): void
    {
        $this->exclRouterFiles = $routerFiles;
    }

    /**
     * Enables Output buffers and template engins
     * @param  bool   $enableTemplate
     * @return void
     */
    public function enableTemplateEngine(bool $enableTemplate): void
    {
        $this->hasTempEngine = $enableTemplate;
    }

    /**
     * Enables database engine (some times when doing some cli commands it can be a good idea to leave it off)
     * @param  bool   $enableDatabase
     * @return void
     */
    public function enableDatabaseEngine(bool $enableDatabase): void
    {
        $this->hasDBEngine = $enableDatabase;
    }

    /**
     * Set language dir
     * @param string $dir
     * @return void
     */
    public function setLangDir(string $dir): void
    {
        putenv("APP_LANG_DIR={$dir}");
    }


    /**
     * Enables the pretty error handler
     * @return void
     */
    public function enablePrettyErrorHandler(): void
    {
        $this->errorHandler = "PrettyPageHandler";
    }

    /**
     * Enables the json response error handler
     * @return void
     */
    public function enableJsonErrorHandler(): void
    {
        $this->errorHandler = "JsonResponseHandler";
    }

    /**
     * Enables the plain text error handler
     * @return void
     */
    public function enablePlainErrorHandler(): void
    {
        $this->errorHandler = "PlainTextHandler";
    }
}
