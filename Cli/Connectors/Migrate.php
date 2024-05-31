<?php
namespace MaplePHP\Foundation\Cli\Connectors;

use MaplePHP\Container\Interfaces\ContainerInterface;
use MaplePHP\Foundation\Http\Dir;
use MaplePHP\Foundation\Migrate\Migration;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Prompts\PromptException;
//use MaplePHP\Query\Exceptions\QueryCreateException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

class Migrate extends AbstractCli
{
    const PROMPT = [];
    private array $classes;

    public function __construct(ContainerInterface $container, RequestInterface $request, Dir $dir)
    {
        parent::__construct($container, $request);

        $classesA = $this->getAllClassesInDir($dir->getPublic(), 'database/migrations');
        // Class B: borde inte migreras den borde flyttas istället.
        $classesB = $this->getAllClassesInDir($dir->getPublic(), 'app/Libraries/Foundation/Migrate/Tables', 'MaplePHP\Foundation\Migrate\Tables');
        $this->classes = $classesA+$classesB;

        $this->addPrompt('migrate',  [
            "migration" => [
                "type" => "select",
                "message" => "Choose migration",
                "items" => ([0 => 'All']+$this->classes),
                "description" => "Choose a migration class",
            ],
            "read" => [
                "type" => "hidden",
                "message" => "Read sql output without migration",
            ],
        ]);
    }

    /**
     * Create migration
     *
     * @param Migration $mig
     * @return void
     * @throws PromptException
     */
    public function migrate(Migration $mig): void
    {
        $action = 'migrate';
        if($this->hasPrompt($action)) {
            $promptData = $this->filterSetArgs($this->protocol[$action]);
            $this->prompt->setTitle("Install the database");
            $this->prompt->set($promptData);

            try {
                $prompt = $this->prompt->prompt();
                if($prompt) {
                    if($prompt['migration'] === 0) {
                        foreach($this->classes as $class) {
                            $this->migrateClass($mig, $class);
                        }
                    } else {
                        $this->migrateClass($mig, $prompt['migration']);
                    }
                }

            } catch (PromptException $_e) {
                $this->command->error("The package \"$action\" is not configured correctly!");

            } catch (Exception $e) {
                throw $e;
            }

        } else {
            $this->command->error("The package \"$action\" do not exists!");
        }
    }

    /**
     * Might add to service if it gets more complex
     *
     * @param Migration $mig
     * @param string $class
     * @return void
     * @throws \Exception
     */
    private function migrateClass(Migration $mig, string $class): void
    {
        if(class_exists($class)) {
            $mig->setMigration($class);
            if(isset($this->args['read'])) {
                $this->command->message($mig->getBuild()->read());
            } else {
                $msg = $mig->getBuild()->create();
                $this->command->message($mig->getBuild()->getMessage($msg, "$class has successfully migrated!"));
            }

        } else {
            $this->command->error("The migration does not exists!");
        }
    }

    /**
     * Will
     *
     * @param string $baseDir
     * @param string $dir
     * @param string|null $namespace
     * @return array
     */
    private function getAllClassesInDir(string $baseDir, string $dir, ?string $namespace = null): array
    {
        $classes = [];
        if(is_null($namespace)) {
            $namespace = str_replace('/', '\\', $dir);
        }
        $dir = $baseDir.$dir;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($rii as $file) {
            if (!$file->isDir()) {
                $filePath = basename($file->getRealPath());
                $end = explode(".", $filePath);
                $end = end($end);
                if($end === "php") {
                    $className = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $filePath);
                    $class = $namespace . '\\' . $className;
                    $classes[$class] = $class;
                }
            }
        }
        return $classes;
    }
}
