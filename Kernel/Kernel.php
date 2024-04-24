<?php

declare(strict_types=1);

namespace MaplePHP\Foundation\Kernel;

use MaplePHP\Foundation\Kernel\App;
use MaplePHP\Http;
use MaplePHP\Handler;
use MaplePHP\Container\Container;

class Kernel
{
    
    private $dir;
    private $rootDir;
    private $stream;
    private $response;
    private $request;
    private $env;
    private $container;
    private $routes;
    private $emitter;
    private $app;

    public function __construct(string $dir, ?string $rootDir = null)
    {
        $this->dir = $dir;
        $this->rootDir = is_null($rootDir) ? $dir : $rootDir;
        $this->stream = new Http\Stream(Http\Stream::TEMP);
        $this->response = new Http\Response($this->stream);
        $this->env = new Http\Environment();
        $this->request = new Http\ServerRequest(new Http\Uri($this->env->getUriParts([
            "dir" => $this->dir,
            "rootDir" => $this->rootDir
        ])), $this->env);

        $this->init();
    }

    public function getRequest() {
        return $this->request;
    }

    private function init(): void
    {
        $this->container = new Container();
        $this->routes = new Handler\RouterDispatcher($this->response, $this->request);
        $this->emitter = new Handler\Emitter($this->container);
        $this->app = new App($this->emitter, $this->routes);
    }
    
    public function run(?string $path = null): void
    {
        $this->app->enablePrettyErrorHandler();
        $this->app->setContainer($this->container);
        $this->app->enableTemplateEngine(true);
        $this->app->excludeRouterFiles(["cli"]);
        //$this->emitter->errorHandler(false, false, true, "{$dir}storage/logs/error.log");
        // bool $displayError, bool $niceError, bool $logError, string $logErrorFile
        //$this->emitter->errorHandler(true, true, true, "{$dir}storage/logs/error.log");

        // Set current URI path
        //$param = $this->request->getQueryParams();
        //$path = ($param['page'] ?? "");
        if(is_null($path)) {
            $path = $this->request->getUri()->getPath();
        }

        $this->routes->setDispatchPath($path);
        $this->app->run();

    }

    /**
     * Setup a MySql Connection
     * @return void
     */
    public function runCli(array $argv): void
    {
        $this->request = new Http\ServerRequest(new Http\Uri($this->env->getUriParts([
            "dir" => $this->dir,
            "rootDir" => $this->rootDir,
            "argv" => $argv
        ])), $this->env);

        $this->init();

        $this->app->enableJsonErrorHandler();
        $this->app->setContainer($this->container);
        $this->app->setRouterFiles(["cli"]);
        $this->app->enableTemplateEngine(false);
        if(($argv[1] ?? NULL) === "config") $this->app->enableDatabaseEngine(false);

        // bool $displayError, bool $niceError, bool $logError, string $logErrorFile
        //$emitter->errorHandler(false, false, true, "/var/www/html/systems/logger-cli.txt");

        // Set current URI path
        $this->routes->setRequestMethod("CLI");
        $this->routes->setDispatchPath($this->request->getCliKeyword());
        $this->app->run();
    
    }

}
