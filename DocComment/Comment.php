<?php

namespace MaplePHP\Foundation\DocComment;

class Comment
{
    private $class;

    public function __construct(string|object $class)
    {
        $this->class = (is_object($class)) ? $class::class : $class;
    }

    /**
     * Get Class name
     * @return string
     */
    public function getClass(bool $base = false): string
    {
        if($base) {
            $expClass = explode('\\', $this->class);
            return end($expClass);
        }
        return $this->class;
    }

    /**
     * Get all methods
     * @return array
     */
    public function getAllMethods(): array
    {
        return get_class_methods($this->class);
    }

    /**
     * Get parsed doc comment
     * @param string $method
     * @return string|false
     * @throws \ReflectionException
     */
    public function getDocComment(string $method): array|false
    {
        $reflection = new \ReflectionClass($this->class);
        $inst = $reflection->getMethod($method);
        $docComment = $inst->getDocComment();
        if ($docComment !== false) {
            $arr = $this->parseDocComment($docComment);
            $arr['method'] = $method;
            return $arr;
        }
        return false;
    }

    /**
     * Parse the Doc comment to array
     * @param string $docComment
     * @return array
     */
    protected function parseDocComment(string $docComment): array {
        $arr = [];
        $lines = preg_split('/\r\n|\r|\n/', $docComment);
        foreach ($lines as $line) {
            $line = trim($line, "/* \t\n\r\0\x0B");
            if (preg_match('/^@(\w+)\s+(.*)$/', $line, $matches)) {
                $tag = $matches[1];
                $desc = $matches[2];
                $arr[$tag] = [];
                if(isset($arr[$tag])) {
                    $arr[$tag][] = $desc;
                }

            } else {
                if (!isset($arr['description'])) {
                    $arr['description'] = [];
                }
                $arr['description'][] = $line;
            }
        }

        // Flatten description array to a string
        if (isset($arr['description'])) {
            $arr['description'] = implode(' ', $arr['description']);
        }
        return $arr;
    }
}
