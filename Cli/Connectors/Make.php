<?php
namespace MaplePHP\Foundation\Cli\Connectors;

use Exception;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Foundation\Http\Dir;
use MaplePHP\Foundation\Http\Provider;
use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Http\UploadedFile;
use MaplePHP\Prompts\PromptException;
use RuntimeException;

class Make extends AbstractCli
{
    protected Dir $dir;
    protected array $protocol;
    protected array $items;
    protected string $cliDir;

    const PROMPT = [
    ];

    public function __construct(ContainerInterface $container, RequestInterface $request, Provider $provider)
    {
        parent::__construct($container, $request, $provider);

        $this->cliDir = realpath(__DIR__ . "/..");
        $this->buildPrompt($this->cliDir);
    }

    /**
     * Make file options
     * @methodExtends prompt
     * @param Dir $dir
     * @param UrlInterface $url
     * @return void
     * @throws PromptException
     */
    public function make(Dir $dir, UrlInterface $url): void
    {
        $action = $url->select("action")->get();
        if($this->hasPrompt($action)) {

            $promptData = $this->protocol[$action];
            $promptData = $this->filterSetArgs($promptData);
            $this->prompt->setTitle("Installing $action");
            $this->prompt->set($promptData);

            try {
                $prompt = $this->getFilterVal($this->prompt->prompt());
                if($prompt) {

                    $addedFiles = [];
                    $make = $this->items[$action][$prompt['type']];
                    $data = $this->prepareMake($prompt['name'], $make);

                    foreach($make as $key => $file) {
                        $overwriteFile = true;
                        $templateFile = $this->cliDir . "/make/" . $file['file'] . ".php";
                        $createFileName = ($data['fileName'][$key] ?? "");
                        $path = $this->mkdir($dir, $file['file']); // Utilize the same dir structure

                        if(is_file($path . "/" . $createFileName . ".php")) {
                            $overwriteFile = $this->command->confirm("File exists: " . $path . "/" . $createFileName . ".php\nDo you want to overwrite?");
                        }

                        if($overwriteFile) {
                            $stream = new Stream($templateFile);
                            $makeContent = $stream->getContents();
                            $makeContent = str_replace($data['find'], $data['replace'], $makeContent);

                            $stream = new Stream(Stream::TEMP);
                            $stream->write($makeContent);
                            $upload = new UploadedFile($stream);
                            $upload->moveTo($path . "/" . $createFileName . ".php");
                            $addedFiles[] = $path . "/" . $createFileName . ".php";
                        }
                    }

                    $this->command->message("");
                    if(count($addedFiles) > 0) {
                        foreach($addedFiles as $file) {
                            $this->command->approve("File created:" . $file);
                        }
                    } else {
                        $this->command->message("No files where added");
                    }
                }

            } catch (PromptException $e) {
                $this->command->error("The package \"$action\" is not configured correctly!");

            } catch (Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Will build the make prompt
     * @param $cliDir
     * @return void
     */
    private function buildPrompt($cliDir): void
    {
        $this->items = $this->makeData($cliDir);
        foreach($this->items as $type => $value) {

            $items = [];
            foreach ($value as $key => $_prompt) $items[$key] = $key;

            $this->addPrompt($type,  [
                "name" => [
                    "type" => "text",
                    "message" => "Choose a $type name",
                    "validate" => [
                        "length" => [1, 60],
                        "pregMatch" => ["a-zA-Z_"],
                    ],
                    "error" => function ($error) {
                        if($error === "length") {
                            return "Required (1-60 characters)";
                        }
                        return "No special characters (\"a-z\", \"A-Z\" and \"_\")";
                    },
                ],
                "type" => [
                    "type" => "select",
                    "message" => "Choose a $type type",
                    "items" => $items,
                    "help" => false
                ]
            ]);
        }
    }

    /**
     * Get make path and create missing directories
     * @param Dir $dir
     * @param string $file
     * @return string
     */
    private function mkdir(Dir $dir, string $file): string
    {
        $exp = explode("/", $dir->getDir($file));
        array_pop($exp);
        $path = implode("/", $exp);
        if(!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Get make data stream
     * @param $cliDir
     * @return false|mixed
     */
    private function makeData($cliDir) {
        try {
            $stream = new Stream("$cliDir/make/make.json");
            $result = $stream->getContents();
            return json_decode($result, true);


        } catch (RuntimeException $e) {
            $this->command->error($e->getMessage());
        }
        return false;
    }

    /**
     * This will propagate and prepare some data to be used to make files
     * @param string $name
     * @param array $make
     * @return array
     */
    private function prepareMake(string $name, array $make): array
    {
        // Pre propagate some variables
        $fileName = $replace = $find = [];
        foreach($make as $key => $file) {
            $prefix = ucfirst($name);
            $suffix = ucfirst(trim(str_replace("%s", "", $file['name'])));
            $fileName[$key] = $prefix.$suffix;
            $find[] = "___{$suffix}___";
            $replace[] = $fileName[$key];
        }

        return [
            "find" => $find,
            "replace" => $replace,
            "fileName" => $fileName
        ];
    }
}
