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
    private $stream;
    private $response;
    private $request;
    private $env;
    private $container;
    private $routes;
    private $emitter;
    private $app;

    public function __construct(string $dir)
    {

        $this->dir = $dir;

        $this->stream = new Http\Stream(Http\Stream::TEMP);
        $this->response = new Http\Response($this->stream);
        $this->env = new Http\Environment();
    }

    private function init(): void
    {
        $this->container = new Container();
        $this->routes = new Handler\RouterDispatcher($this->response, $this->request);
        $this->emitter = new Handler\Emitter($this->container);
        $this->app = new App($this->emitter, $this->routes);
    }
    
    public function run(): void
    {
        $this->request = new Http\ServerRequest(new Http\Uri($this->env->getUriParts([
            "dir" => $this->dir
        ])), $this->env);


        $this->init();

        $this->app->enablePrettyErrorHandler();
        $this->app->setContainer($this->container);
        $this->app->enableTemplateEngine(true);
        $this->app->excludeRouterFiles(["cli"]);

        //$this->emitter->errorHandler(false, false, true, "{$dir}storage/logs/error.log");
        // bool $displayError, bool $niceError, bool $logError, string $logErrorFile
        //$this->emitter->errorHandler(true, true, true, "{$dir}storage/logs/error.log");

        // Set current URI path
        $param = $this->request->getQueryParams();
        $this->routes->setDispatchPath("/" . ($param['page'] ?? ""));
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
