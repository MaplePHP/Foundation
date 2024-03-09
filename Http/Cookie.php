<?php

namespace MaplePHP\Foundation\Http;

use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Http\Cookies;
use BadMethodCallException;

class Cookie
{

    const SAMESITE = "Strict";
    const HTTPONLY = true;

    private $url;
    private $cookies;

    /**
     * Recommended standard settings
     * If you want a more loose cookie then do it from a new instance
     * @param UrlInterface $url
     */
    public function __construct(UrlInterface $url)
    {
        $this->url = $url;
        $scheme = $this->url->getUri()->getScheme();
        $this->cookies = new Cookies("/", $this->url->getUri()->getHost(), ($scheme === "https"), static::HTTPONLY);
        // Only read modify on same site
        $this->cookies->setSameSite(static::SAMESITE);
    }

    public function inst()
    {
        return $this->cookies;
    }

    public function __call($method, $args)
    {
        if (method_exists($this->cookies, $method)) {
            return call_user_func_array([$this->cookies, $method], $args);
        } else {
            throw new BadMethodCallException('The method "' . $method . '" does not exist in the Cookies Class!', 1);
        }
    }
}
