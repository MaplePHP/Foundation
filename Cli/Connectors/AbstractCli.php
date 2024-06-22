<?php

namespace MaplePHP\Foundation\Cli\Connectors;

use MaplePHP\Foundation\DocComment\Comment;
use MaplePHP\Foundation\Http\Provider;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Prompts\Prompt;
use MaplePHP\Prompts\Command;
use ReflectionException;

abstract class AbstractCli implements CliInterface
{
    protected ContainerInterface $container;
    protected array $args;
    protected array $protocol;
    protected Prompt $prompt;
    protected Command $command;
    protected Provider $provider;
    private array $input = [];

    public function __construct(ContainerInterface $container, RequestInterface $request, Provider $provider)
    {
        $this->container = $container;
        $this->args = $request->getCliArgs();
        $this->prompt = new Prompt();
        $this->command = new Command();
        $this->protocol = static::PROMPT;
        $this->provider = $provider;
    }

    /**
     * Extend the prompt
     * @param string $promptName
     * @param array $arr
     * @return void
     */
    protected function addPrompt(string $promptName, array $arr): void
    {
        $this->protocol[$promptName] = $arr;
    }

    /**
     * Check if prompt exists
     * @param string $promptName
     * @return bool
     */
    protected function hasPrompt(string $promptName): bool
    {
        return isset($this->protocol[$promptName]);
    }

    /**
     * Filter out cli command for hidden and passed argv
     * @param array $arr
     * @return array
     */
    protected function filterSetArgs(array $arr): array
    {
        $new = array();
        foreach ($arr as $key => $val) {
            if(!isset($this->args[$key])) {
                $type = ($val['type'] ?? "");
                if($type === "hidden") {
                    $this->input[$key] = ($val['default'] ?? "");
                } else {
                    $new[$key] = $val;
                }
            } else {
                $this->input[$key] = $this->args[$key];
            }
        }
        return $new;
    }

    /**
     * Get filtered values
     * @param array $arr
     * @return array
     */
    protected function getFilterVal(array $arr): array
    {
        return array_merge($arr, $this->input);
    }

    /**
     * Extract help text with doc block comment
     * @param object|string $className
     * @return Comment
     * @throws ReflectionException
     */
    protected function getCommentBlock(object|string $className): Comment
    {
        $block = new Comment($className);
        $this->command->title($block->getClass(true));
        $methods = $block->getAllMethods();
        foreach ($block->getAllMethods() as $method) {
            $comment = $block->getDocComment($method);
            if(isset($comment['description'])) {
                $class = strtolower($block->getClass(true));
                if(($comment['methodExtends'][0] ?? "") === "prompt") {
                    foreach($this->protocol as $meth => $_arr) {
                        if(!in_array($meth, $methods)) {
                            $this->generateHelpText($comment, $class, $meth);
                        }
                    }

                } else {
                    $this->generateHelpText($comment, $class, $method);
                }
            }

        }
        return $block;
    }

    /**
     * Will help generate help texts in PHP Maple Cli
     * Example: Extending Prompt Array items
     * --help:
     * description: Add parameter types usage example to prompt help text
     * default: Add Default value to parameter usage example to prompt help text
     * help: Force remove parameter type from usage example in prompt help text
     * @param array $comment
     * @param string $class
     * @param string $method
     * @return void
     */
    private function generateHelpText(array $comment, string $class, string $method) {
        $classA = "$class:$method";
        $length = strlen($classA);
        $padA = str_pad("", 40-$length, " ", STR_PAD_RIGHT);
        $padB = str_pad("", 40, " ", STR_PAD_RIGHT);

        if(isset($this->protocol[$method])) {
            $valid = reset($this->protocol[$method]);
            //isValidPrompt
            if(isset($valid['type']) && isset($valid['message'])) {

                $this->command->statusMsg($classA, false);
                $this->command->message("{$padA}{$comment['description']}");

                $fill = "{$padB} ";
                $this->command->title("{$fill}Arguments");
                foreach ($this->protocol[$method] as $arg => $data) {
                    if((bool)($data['help'] ?? true) !== false) {
                        $message = (!empty($data['description'])) ? $data['description'] : ($data['message'] ?? "");
                        $addEg = (!empty($data['default'])) ? " (example: {$data['default']})" : "";
                        $this->command->approve("$fill--$arg: ", false);
                        $this->command->message($message . $addEg);
                    }
                }
                $this->command->message("\n");
            }
        }
    }

    private function isValidPrompt() {
    }

    /**
     * Access help (or with added flag --help)
     * @return void
     * @throws ReflectionException
     */
    public function help(): void
    {
        $this->command->title("\nMaplePHP CLI Commands");
        $this->command->message("Below are all the available inputs for the MaplePHP CLI commands.\n");
        $block = $this->getCommentBlock($this);
        $class = strtolower($block->getClass(true));
        $this->command->title("\nExample usage: ", false);
        $this->command->message("php cli $class:[action] --arg=1 --arg=2");
    }

}
