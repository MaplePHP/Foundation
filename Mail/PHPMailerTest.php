<?php

namespace MaplePHP\Foundation\Mail;

use MaplePHP\Container\Interfaces\EventInterface;
use MaplePHP\Foundation\Http\Provider;

class PHPMailerTest implements EventInterface
{

    private $provider;
    private $output = [];

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }
    
    public function resolve(): void
    {
        echo $this->provider->logger()->getMessage()."<br>";
    }

}
