<?php
namespace MaplePHP\Foundation\Cli\Connectors;

use Exception;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Foundation\Http\Dir;
use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Http\Env;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Http\UploadedFile;
use MaplePHP\Prompts\PromptException;

class Install extends AbstractCli
{
    protected Dir $dir;
    protected array $protocol;

    const PROMPT = [
        'install' => [
            "host" => [
                "type" => "text",
                "message" => "App name",
                "default" => "My app",
                "validate" => [
                    "length" => [1,60]
                ],
                "error" => "Required",
                "description" => "Set your app name",
            ],
            "lang" => [
                "type" => "text",
                "message" => "Language",
                "default" => "en",
                "validate" => [
                    "length" => [2,2]
                ],
                "error" => "Required and must be 2 characters",
                "description" => "Set your default language",
            ],
            "maintainer_email" => [
                "type" => "text",
                "message" => "Your email",
                "validate" => [
                    "length" => [1,160],
                    "email" => []
                ],
                "error" => "Required and must be email",
                "description" => "Set your maintainer email",
            ],
            "maintainer_name" => [
                "type" => "text",
                "message" => "Your full name",
                "validate" => [
                    "length" => [1,160]
                ],
                "error" => "Required",
                "description" => "Set your maintainer full name",
            ],
            "debug" => [
                "type" => "hidden",
                "message" => "Debug mode",
                "default" => "1",
                "validate" => [
                    "length" => [1,1],
                    "int" => []
                ],
                "error" => "Required",
                "description" => "Set debug mode",
            ],
            "charset" => [
                "type" => "hidden",
                "message" => "Set your charset",
                "default" => "UTF-8",
                "validate" => [
                    "length" => [1,60]
                ],
                "error" => "Required"
            ]
        ]
    ];

    public function __construct(ContainerInterface $container, RequestInterface $request)
    {
        parent::__construct($container, $request);
        foreach($_ENV['config'] as $key => $value) {
            if(is_array($value)) {
                $this->addPrompt($key,  $value);
            }
        }
    }

    /**
     * Install the MaplePHP framework
     * @param Dir $dir
     * @return void
     * @throws PromptException
     */
    public function install(Dir $dir): void
    {
        $file = $dir->getDir() . ".env";
        $this->prompt->setTitle("Installing the framework");
        $this->prompt->set($this->filterSetArgs(static::PROMPT[__FUNCTION__]));
        $prompt = $this->getFilterVal($this->prompt->prompt());

        $this->updateEnvFile("app", $file, $prompt);
        $this->command->approve("The framework has been successfully installed.");
    }

    /**
     * Install to the framework
     * @methodExtends prompt
     * @param Dir $dir
     * @param UrlInterface $url
     * @return void
     * @throws PromptException
     */
    function config(Dir $dir, UrlInterface $url): void
    {
        $action = $url->select("action")->get();
        if($action === "install") {
            $this->install($dir);

        } else {
            if($this->hasPrompt($action)) {
                $promptData = $this->protocol[$action];
                $promptData['confirm'] = [
                    'type' => 'confirm',
                    'message' => 'Are you sure you want to proceed?'
                ];
                $promptData = $this->filterSetArgs($promptData);
                $this->prompt->setTitle("Installing $action");
                $this->prompt->set($promptData);
                try {
                    $prompt = $this->prompt->prompt();
                    if($prompt) {
                        unset($prompt['confirm']);
                        $file = $dir->getDir() . ".env";
                        $this->updateEnvFile($action, $file, $prompt);
                        $this->command->approve("The package has been successfully installed.");
                    }

                } catch (PromptException $e) {
                    $this->command->error("The package \"$action\" is not configured correctly!");
                } catch (Exception $e) {
                    throw $e;
                }

            } else {
                $this->command->error("The package \"$action\" do not exists!");
            }
        }
    }

    /**
     * Will update the EnvFile (Will be moved to service file)
     * @param string $prefix
     * @param string $file
     * @param array $prompt
     * @return void
     */
    private function updateEnvFile(string $prefix, string $file, array $prompt): void
    {
        $env = new Env($file);
        foreach ($prompt as $key => $value) {
            $nKey = strtoupper("{$prefix}_$key");
            $env->set($nKey, $value);
        }
        $envStream = new Stream(Stream::TEMP);
        $envStream->write($env->generateOutput());
        $upload = new UploadedFile($envStream);
        $upload->moveTo($file);
    }
}
