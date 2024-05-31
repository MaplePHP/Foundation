<?php

declare(strict_types=1);

namespace MaplePHP\Foundation\Kernel;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Handler\Emitter;
use MaplePHP\Handler\RouterDispatcher;
use MaplePHP\Http\Dir;
use MaplePHP\Http\Env;
use MaplePHP\DTO\Format\Arr;
use MaplePHP\DTO\Format\Local;
use MaplePHP\Container\Reflection;
use MaplePHP\Query\Connect;
use MaplePHP\Query\Exceptions\ConnectException;
use MaplePHP\Query\Handlers\MySQLHandler;
use MaplePHP\Query\Handlers\SQLiteHandler;

class App extends AppConfigs
{
    protected Emitter $emitter;
    protected RouterDispatcher $dispatcher;
    protected ?object $whoops = null;

    private $installed = false;

    public function __construct(Emitter $emitter, RouterDispatcher $dispatcher)
    {
        $this->emitter = $emitter;
        $this->dispatcher = $dispatcher;
        $this->dir = new Dir(
            $this->dispatcher->request()->getUri()->getDir(), 
            $this->dispatcher->request()->getUri()->getRootDir()
        );
    }

    /**
     * Setup a MySql Connection
     * @return void
     * @throws ConnectException
     */
    protected function setupMysqlConnection(): void
    {
        $connect = $this->getenv("MYSQL_HOST");
        $database = $this->getenv("MYSQL_DATABASE");

        if ($this->hasDBEngine && is_string($connect) && is_string($database)) {

            $port = (int)$this->getenv("MYSQL_PORT");
            if($port === 0) $port = 3306;

            $handler = new MySQLHandler(
                $connect,
                $this->getenv("MYSQL_USERNAME"),
                $this->getenv("MYSQL_PASSWORD"),
                $database,
                $port,
            );

            //$handler = new SqliteHandler($this->dir->getRoot("database/database.sqlite"));

            $handler->setCharset($this->getenv("MYSQL_CHARSET"));
            $handler->setPrefix($this->getenv("MYSQL_PREFIX"));

            $connect = Connect::setHandler($handler);
            $connect->execute();

            /*
            $select = Connect::getInstance()::select("*", "logger");
            $ww = $select->fetch();
             */

        }
    }

    /**
     * Setup configs and install env
     * @return void
     */
    protected function setupConfig(): void
    {
        // LOAD env from the .env file first.
        $file = $this->dir->getRoot() . ".env";
        $this->attr['NONCE'] = bin2hex(random_bytes(16));
        $this->attr['APP_DIR'] = $this->dir->getRoot();
        //$this->container->set("nonce", $this->attr);

        $env = new Env();
        if (is_file($file)) {
            $this->installed = true;
            $env->loadEnvFile($file);
            $env->putenvArray($this->attr + $this->getConfigFileData());
            $this->attr += $env->getData();

        } else {
            // Create installation screen
            $put = $this->getConfigFileData();
            $put['config']['routers']['load'] = ["cli"];
            unset($put['config']['mysql']);
            $env->putenvArray($this->attr + $put);
            
            //$response, $request
            $this->dispatcher->get("/", function () {
                $this->container->get("view")->setIndex(function() {
                    $out = "";
                    $out .= "<article style=\"padding: 10% 30px;\">";
                    $out .= "<section style=\"max-width: 600px; margin: 0 auto; text-align: center;\">";
                    $out .= "<h6 style=\"margin:0 0 5px 0; font-size: 1.4rem; letter-spacing: 2px; text-transform: uppercase; line-height: 1.2em; font-weight: bold;\">Welcome to MaplePHP</h6>";
                    $out .= "<h1 style=\"margin:0 0 10px 0; font-size: 4.4rem; line-height: 1.2em; font-weight: bold;\">Install the application</h1>";
                    $out .= "<p style=\"font-size: 1.8rem; line-height: 1.6em;\">You need to first install the application in order to use it. Execute the command bellow in you command line:</p>";
                    $out .= "<pre style=\"font-size: 1.4rem; line-height: 1.6em; padding: 10px; background: #F1F1F1;\">php cli config install --type=app</pre>";
                    $out .= "</section>";
                    $out .= "</article>";
                    echo sprintf($this->htmlDocPlaceholder(), $out);
                });
            });
        }

        // Set default envars, which can be used in config files!
        $env->execute();
        $this->attr = array_merge($this->attr, $env->getData());
    }

    /**
     * Setup Routers
     * @return void
     */
    protected function setupRouters(): void
    {
        if (($config = $this->getConfig("routers"))) {
            if ((bool)($config['cache'] ?? false)) {
                $dir = $this->getConfigDir($config['cacheFile']['path']);
                $file = $config['cacheFile']['prefix'] . $config['cacheFile']['file'];
                $this->dispatcher->setRouterCacheFile("{$dir}{$file}", false);
            }

            $routerFiles = is_null($this->routerFiles) ? ($config['load'] ?? null) : $this->routerFiles;
            $routerFiles = $this->defualtRoutes($routerFiles);
            if (is_array($routerFiles)) {
                foreach ($routerFiles as $file) {
                    if (!in_array($file, $this->exclRouterFiles)) {
                        $dir = $this->dir->getRoot();
                        if(strpos($file, "./") === 0) {
                            $path = realpath(dirname(__FILE__).substr($file, 1).".php");
                        } else {
                            $path = "{$dir}app/Http/Routes/{$file}.php";
                        }
                        $this->includeRoutes($this->dispatcher, $path);
                    }
                }
            }
        }
    }

    protected function defualtRoutes(array $routerFiles) {
        if($this->dispatcher->getMethod() === "CLI") {
            $routerFiles[] = "./../Cli/Routers/default";
        }
        return $routerFiles;
    }

    protected function includeRoutes(RouterDispatcher $routes, string $fullPathToFile): void
    {
        if (!is_file($fullPathToFile)) {
            throw new \Exception("The file \"{$fullPathToFile}\" do not exist. Make sure it is in the right directory!", 1);
        }
        require_once($fullPathToFile);
    }

    /**
     * Setup languages
     * @return void
     */
    protected function setupLang(): void
    {
        if ($appLang = getenv("APP_LANG")) {
            Local::setLang($appLang);
        }

        $appLangDir = getenv("APP_LANG_DIR");
        $this->setLangDir(($appLangDir) ? $appLangDir : $this->dir->getRoot() . "resources/lang/");

        // Re-set varible, might have changed above
        if ($appLangDir = getenv("APP_LANG_DIR")) {
            Local::setDir($appLangDir);
        }
    }

    /**
     * Setup view
     * @return void
     */
    protected function setupViews(): void
    {
        if ($this->hasTempEngine) {
            $this->emitter->view()
            ->setIndexDir($this->dir->getRoot() . "resources/")
            ->setViewDir($this->dir->getRoot() . "resources/views/")
            ->setPartialDir($this->dir->getRoot() . "resources/partials/")
            ->bindToBody(
                "httpStatus",
                Arr::value($this->dispatcher->response()::PHRASE)->unset(200, 201, 202)->arrayKeys()->get()
            )
            ->setIndex("index")
            ->setView("main");
        }
    }

    /**
     * Setup the dispatcher
     * @return void
     */
    protected function setupDispatch(?callable $call = null): void
    {
        $this->dispatcher->dispatch(function (
            int $dispatchStatus,
            ResponseInterface &$response,
            RequestInterface $request,
            UrlInterface|null $url

        ) use ($call): ResponseInterface {
            switch ($dispatchStatus) {
                case RouterDispatcher::NOT_FOUND:
                    $response = $response->withStatus(404);
                    break;
                case RouterDispatcher::METHOD_NOT_ALLOWED:
                    $response = $response->withStatus(403);
                    break;
                case RouterDispatcher::FOUND:
                    $this->defaultInterfaces($response, $request, $url);
                    if (is_callable($call)) {
                        $response = $call($response);
                    }
                    break;
            }
            return $response;
        });
    }

    /**
     * Add a class that will where it's instance will be remembered through the app and its
     * controllers, To do this, you must first create an interface of the class, which will
     * become its uniqe identifier.
     * @return void
     */
    final protected function defaultInterfaces($response, $request, $url): void
    {
        Reflection::interfaceFactory(function ($className) use ($request, &$response, $url) {
            switch ($className) {
                case "UrlInterface":
                    return $url;
                case "DirInterface":
                    return $this->dir;
                case "ContainerInterface":
                    return $this->container;
                case "RequestInterface":
                    return $request;
                case "ResponseInterface":
                    return $response;
                default:
                    return null;
            }
        });
    }

    /**
     * Setup error handling, enables with APP_DEBUG
     * @psalm-suppress InvalidArgument
     * @return void
     */
    protected function setupErrorHandler(): void
    {
        if (getenv("APP_DEBUG")) {
            if (!is_null($this->whoops) || (class_exists('\Whoops\Run') && !is_null($this->errorHandler))) {
                $class = "\\Whoops\\Handler\\{$this->errorHandler}";
                if (is_null($this->whoops)) {
                    $this->whoops = new \Whoops\Run();
                    $this->whoops->pushHandler($this->getWhoopsHandler($class));
                    $this->whoops->register();
                } else {
                    if (!$this->hasWhoopsHandler($class)) {
                        $this->whoops->pushHandler($this->getWhoopsHandler($class));
                        $this->whoops->register();
                    }
                }
            } else {
                $this->emitter->errorHandler(true, true, true, $this->dir->getRoot() . "storage/logs/error.log");
            }
        }
    }

    /**
     * Set response headers
     * @return ResponseInterface
     */
    protected function setupHeaders($response): ResponseInterface
    {
        if ($ttl = (int)getenv("APP_CACHE_TTL")) {
            $this->emitter->setDefaultCacheTtl($ttl);
        }
        if (isset($this->attr['config']['headers'])) {
            foreach ($this->attr['config']['headers'] as $key => $value) {
                if (!$response->hasHeader($key)) {
                    $response = $response->withHeader($key, $value);
                }
            }
        }

        return $response;
    }

    /**
      * Run the application
      * @return void
      */
    public function run(): void
    {
        $this->setupConfig();
        $this->setupErrorHandler();
        $this->setupMysqlConnection();
        $this->setupViews();
        $this->setupRouters();
        $this->setupLang();

        $this->setupDispatch(function ($response) {
            return $this->setupHeaders($response);
        });
        
        $response = $this->dispatcher->response();
        $request = $this->dispatcher->request();

        // Will force https address.
        if ((int)getenv("APP_FORCE_SSL") === 1 && !$request->isSSL()) {
            $location = $request->getUri()->withScheme("https")->withQuery("")->withPort(null)->getUri();
            $response->withStatus(301)->location($location);
        }

        if (!$this->container->has("url")) {
            $this->container->set("url", $this->dispatcher->url());
            if($this->installed === false) {
                $this->container->set("TempServiceUrl", '\MaplePHP\Foundation\Http\Url');
                $this->dispatcher->url()->setHandler($this->container->get('TempServiceUrl'));
            }
        }

        if (!($response instanceof ResponseInterface)) {
            throw new \Exception("Fatal error: The apps ResponseInterface has not been initilized!", 1);
        }
        
        $type = $response->getHeaderLineData("content-type");
        switch (($type[0] ?? "text/html")) {
            case "text/html":
                $this->enablePrettyErrorHandler();
                break;
            case "application/json":
                $this->enableJsonErrorHandler();
                break;
            default:
                $this->enablePlainErrorHandler();
        }


        // Handle error response IF contentType has changed!
        $this->setupErrorHandler();

        // If you set a buffered response string it will get priorities agains all outher response
        $this->emitter->outputBuffer($this->dispatcher->getBufferedResponse());
        $this->emitter->run($response, $request);
    }


    private function htmlDocPlaceholder(): string
    {
        return '<!DOCTYPE html>
        <html lang="en" style="font-size: 10px;">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
        </head>
        <body style="margin:0; font-family: Arial, sans-serif; line-height: 150%%;">
            %s
        </body>
        </html>';
    }
}
