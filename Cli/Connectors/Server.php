<?php

namespace MaplePHP\Foundation\Cli\Connectors;

//use MaplePHP\Foundation\Cli\Connectors\CliInterface;
//use MaplePHP\Http\Env;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Foundation\Cli\StandardInput;
use MaplePHP\Foundation\Http\Dir;


class Server implements CliInterface
{
    protected ContainerInterface $container;
    protected array $args;
    protected Dir $dir;
    protected StandardInput $cli;

    public function __construct(ContainerInterface $container, RequestInterface $request, Dir $dir, StandardInput $cli)
    {
        $this->container = $container;
        $this->args = $request->getCliArgs();
        $this->dir = $dir;
        $this->cli = $cli;
    }

    public function start(): ResponseInterface
    {
        $localIPs = ($this->args['host'] ?? "localhost");
        $port = (int)($this->args['port'] ?? 8080);
        $pubDir = ($this->args['dir'] ?? "public");
        $pathPublicDir = realpath($this->dir->getPublic($pubDir));
        if (!$pathPublicDir) {
            $this->cli->write("Invalid directory path to public directory");
            return $this->cli->getResponse();
        }

        $this->cli->write("MaplePHP Server has started, visit bellow to display you app.");
        $this->cli->write("Vist: http://$localIPs:$port");
        $cmd = sprintf('php -S %s -t %s', escapeshellarg("$localIPs:$port"), escapeshellarg($pathPublicDir));
        $out = shell_exec($cmd);
        if($out) {
            $this->cli->write("Could not connect to host!");
        }
        /*
        $cmd = (sprintf('php -S %s:%d -t %s', $localIPs, 8080, escapeshellarg($this->dir->getPublic())));
        $command = "nohup $cmd > /dev/null 2>&1 & echo $! > server.pid";
        echo $command;
        die;
        $out = shell_exec('php -S 10.0.1.220:8080 -t /var/www/html/systems/_phpfuse/');
         */
        return $this->cli->getResponse();
    }

/*
 public function stop()
    {

        $pid = file_get_contents('server.pid');
        shell_exec("kill $pid");
        $this->cli->write("The server has been stopped!");

        return $this->cli->getResponse();
    }
 */

    public function help(): ResponseInterface
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
