<?php

namespace MaplePHP\Foundation\Cli\Connectors;

use MaplePHP\Foundation\Cli\Connectors\CliInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Http\Env;
use MaplePHP\Container\Reflection;
use MaplePHP\Foundation\Cli\StandardInput;
use MaplePHP\Foundation\Http\Dir;

class Server implements CliInterface
{
    protected $container;
    protected $args;
    protected $dir;
    protected $cli;
    protected $port = 8000;

    public function __construct(ContainerInterface $container, RequestInterface $request, Dir $dir, StandardInput $cli, Env $env)
    {
        $this->container = $container;
        $this->args = $request->getCliArgs();
        $this->dir = $dir;
        $this->cli = $cli;
    }


    public function start()
    {
        die("COMING SOON");

        //putenv("")
        $localIPs = $this->cli->getLocalIPAddress();
        $this->cli->write("MaplePHP Server has started, vist bellow to display you app.");
        $this->cli->write("Vist: http://{$localIPs}:8080");
        $cmd = (sprintf('php -S %s:%d -t %s', $localIPs, 8080, escapeshellarg($this->dir->getPublic())));
        //$command = "nohup $cmd > /dev/null 2>&1 & echo $! > server.pid";
        //echo $cmd;
        //die;
        $out = shell_exec('php -S 10.0.1.220:8080 -t /var/www/html/systems/_phpfuse/');

        return $this->cli->getResponse();
    }

    public function stop() 
    {
        /*
        $pid = file_get_contents('server.pid');
        shell_exec("kill $pid");
        $this->cli->write("The server has been stopped!");
         */
        return $this->cli->getResponse();
    }

    public function help()
    {
        $this->cli->write('$ config [type] [--values, --values, ...]');
        $this->cli->write('Type: install, read, create, drop or help');
        $this->cli->write('Values: --key=%s, --value=%s --strict');
        $this->cli->write('--key: The env config key (type: create, drop)');
        $this->cli->write('--value: The env config value (type: create)');
        $this->cli->write('--strict: Will also show hidden configs, (type: read)');
        return $this->cli->getResponse();
    }
}
