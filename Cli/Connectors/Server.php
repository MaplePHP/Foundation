<?php
namespace MaplePHP\Foundation\Cli\Connectors;

use MaplePHP\Foundation\Http\Dir;
use MaplePHP\Prompts\PromptException;

class Server extends AbstractCli
{
    protected Dir $dir;

    const PROMPT = [
        'start' => [
            "host" => [
                "type" => "text",
                "message" => "Host",
                "default" => "localhost",
                "validate" => [
                    "length" => [1,200],
                    "domain" => []
                ],
                "error" => "Not a valid host",
                "description" => "Set hostname",
            ],
            "port" => [
                "type" => "text",
                "message" => "Port",
                "default" => "8282",
                "validate" => [
                    "length" => [4,4],
                    "int" => []
                ],
                "error" => "Not a valid port expecting 4 digits e.g. (0000)",
                "description" => "Set a port number",
            ],
            "path" => [
                "type" => "hidden",
                "message" => "Change the public directory",
                "default" => "public",
                "validate" => [
                    "length" => [1,120]
                ],
                "error" => "Required",
            ]
        ]
    ];

    /**
     * Will start a MaplePHP server
     * @param Dir $dir
     * @return void
     * @throws PromptException
     */
    public function start(Dir $dir): void
    {
        $this->prompt->setTitle("Preparing Server");
        $this->prompt->set($this->filterSetArgs(static::PROMPT[__FUNCTION__]));
        $prompt = $this->getFilterVal($this->prompt->prompt());

        $host = $prompt['host'];
        $port = (int)$prompt['port'];
        $path = ($this->args['path'] ?? "public");
        $pathPublicDir = realpath($dir->getPublic($path));

        if (!$pathPublicDir) {
            $this->command->error("Invalid directory path to public directory");
            return;
        }

        $this->command->title("\nMaplePHP Server has started, visit bellow to display you app.");
        $this->command->statusMsg("Visit: http://$host:$port\n");
        $cmd = sprintf('php -S %s -t %s', escapeshellarg("$host:$port"), escapeshellarg($pathPublicDir));
        $out = shell_exec($cmd);
        if($out) {
            $this->command->error("Could not connect to host!");
        }
    }

}
