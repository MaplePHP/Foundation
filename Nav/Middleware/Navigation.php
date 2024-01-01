<?php

namespace MaplePHP\Foundation\Nav\Middleware;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Handler\Interfaces\MiddlewareInterface;
use MaplePHP\Foundation\Http\Provider;
use MaplePHP\Foundation\Nav\Navbar;

class Navigation implements MiddlewareInterface
{
    protected $provider;
    protected $nav;

    public function __construct(Provider $provider, Navbar $nav)
    {
        $this->provider = $provider;
        $this->nav = $nav;
    }

    /**
     * Before controllers
     * @param  ResponseInterface $response
     * @param  RequestInterface  $request
     * @return void
     */
    public function before(ResponseInterface $response, RequestInterface $request)
    {
        // Set navigate view partial
        $this->provider->view()->setPartial("navigation.!document/navigation|!navigation", [
            "nav" => $this->nav->get()
        ]);

        return $response;
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
