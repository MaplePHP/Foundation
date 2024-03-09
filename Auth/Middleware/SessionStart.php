<?php

namespace MaplePHP\Foundation\Auth\Middleware;

use MaplePHP\Handler\Interfaces\MiddlewareInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Foundation\Security\Session;

class SessionStart implements MiddlewareInterface
{
    const NAME = NULL; // Set a custom seesion name
    const TIME = 360;
    const SSL = true;
    const SAMESITE = true;

    private $container;

    public function __construct(RequestInterface $request, ContainerInterface $container)
    {
        $this->container = $container;
        // Config and prepare session
    }

    /**
     * Start prepared session Before controllers method view but after controllers construct
     * @param  ResponseInterface $response
     * @param  RequestInterface  $request
     * @return void
     */
    public function before(ResponseInterface $response, RequestInterface $request)
    {
        $time = (getenv("SESSION_TIME") !== false) ? (int)getenv("SESSION_TIME") : static::TIME;
        $SSL = (getenv("SESSION_SSL") !== false) ? ((int)getenv("SESSION_SSL") === 1) : static::SSL;

        $session = new Session(
            static::NAME,
            $time,
            "/",
            $request->getUri()->getHost(),
            $SSL,
            static::SAMESITE
        );
        $this->container->set("session", $session);
        $this->container->get("session")->start();
    }

    /**
     * After controllers
     * @param  ResponseInterface $response
     * @param  RequestInterface  $request
     * @return void
     */
    public function after(ResponseInterface $response, RequestInterface $request)
    {
    }
}
