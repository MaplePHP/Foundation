<?php
namespace MaplePHP\Foundation\Cli\Connectors;

use MaplePHP\Foundation\Http\Dir;
use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Unitary\Unit;

class Test
{
    protected Dir $dir;
    protected array $args;
    public function __construct(RequestInterface $request)
    {
        $this->args = $request->getCliArgs();
    }

    /**
     * Run all test suites
     * @param Dir $dir
     * @return void
     * @throws \Exception
     */
    function test(Dir $dir) {
        $unit  = new Unit();
        $path = ($this->args['path'] ?? $dir->getPublic());
        $unit->setArgs($this->args);
        $unit->executeAll($path);
    }
}
