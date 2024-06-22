<?php

namespace MaplePHP\Foundation\Http;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Http\Interfaces\DirInterface;
use MaplePHP\Container\EventHandler;
use MaplePHP\DTO\Format\DateTime;
use MaplePHP\DTO\Format\Str;
use MaplePHP\DTO\Format\Local;
use MaplePHP\DTO\Format\Encode;
use MaplePHP\Query\Connect;
use MaplePHP\Query\DB;
use BadMethodCallException;

class Provider
{
    public static $container;

    public function __construct(
        ContainerInterface $container,
        ResponseInterface $response,
        RequestInterface $request,
        UrlInterface $url,
        DirInterface $dir,
        Url $urlHandler,
        Dir $dirHandler
    ) {
        // Construct all services that should be autoloaded.
        if(is_null(self::$container)) {
            self::$container = $container;

            self::$container->set("url", $url);
            self::$container->set("dir", $dir);
            self::$container->set("response", $response);
            self::$container->set("request", $request);

            $url->setHandler($urlHandler);
            $dir->setHandler($dirHandler);
            
            $this->getConfProviders();
            $this->getBuiltFactories();
        }
    }

    /**
     * Some custom factory providers
     * @return void
     */
    private function getBuiltFactories(): void
    {
        self::$container->set("date", DateTime::value("now")
            ->setLanguage(self::$container->get("lang")->prefix()));

        self::$container->set("string", function ($value) {
            return new Str($value);
        });

        self::$container->set("env", function (string $key, string $fallback = "") {
            $value = (getenv($key) !== false) ? (string)getenv($key) : $fallback;
            return new Str($value);
        });

        self::$container->set("encode", function (array|string $value) {
            return new Encode($value);
        });

        self::$container->set("local", function (string $langKey) {
            $data = [
                "auth" => Local::value("auth"),
                "validate" => Local::value("validate")
            ];
            return ($data[$langKey] ?? null);
        });

        self::$container->set("DB", function (?string $key = null) {
            return Connect::getInstance($key);
        });
    }

    /**
     * Access the service providers from config file
     * @return void
     */
    private function getConfProviders(): void
    {
        $arr = ($_ENV['PROVIDERS_SERVICES'] ?? []);
        if(is_array($arr)) foreach($arr as $name => $class) {

            if(is_array($class)) {
                $event = new EventHandler();
                foreach($class['handlers'] as $handler => $methods) {
                    $event->addHandler($handler, $methods);
                }
                foreach($class['events'] as $handler) {
                    $event->addEvent($handler);
                }
                $class = $event;
            }

            self::$container->set($name, $class);
        }
    }

    /**
     * This will make shortcuts to container.
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        if ($method === "has" || $method === "set") {
            return call_user_func_array([self::$container, $method], $args);
        }
        if ((self::$container instanceof ContainerInterface) && self::$container->has($method)) {
            return self::$container->get($method, $args);
        } else {
            throw new BadMethodCallException('The method "' . $method . '" does not exist in the Container or the ' .
                'Class "' . static::class . '"!', 1);
        }
    }
}
