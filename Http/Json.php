<?php

declare(strict_types=1);

namespace MaplePHP\Foundation\Http;

use MaplePHP\Foundation\Http\Interfaces\JsonInterface;

class Json implements JsonInterface
{
    public const ERROR_MESSAGES = [
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
        JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded',
        JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
        JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given',
        JSON_ERROR_UTF16 => 'Malformed UTF-16 characters, possibly incorrectly encoded'
    ];


    public $data = array("status" => 0, "error" => 0);
    public $fields = array();

    public function __construct()
    {
    }


    public function __toString(): string
    {
        return (string)$this->encode();
    }

    /**
     * New json instance
     * @param  array|string $data
     * @return self
     */
    public function withData(array|string $data): self
    {
        $inst = new self();
        if(is_array($data)) {
            $inst->data = array_merge($this->data, $data);
        } else {
            $inst->data = $this->decode($data);
        }
        return $inst;
    }

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function merge(array $array): self
    {
        $this->data = array_merge($this->data, $array);
        return $this;
    }

    /**
     * Overwrite whole json array
     * @param  array  $array
     * @return self
     */
    public function set($array): self
    {
        $this->data = $array;
        return $this;
    }

    /**
     * Merge array to json array
     * @param  array  $array
     * @return self
     */
    public function mergeTo(string $key, array $array): self
    {
        if (empty($this->data[$key])) {
            $this->data = array_merge($this->data, [$key => $array]);
        } else {
            $this->data[$key] = array_merge($this->data[$key], $array);
        }
        return $this;
    }

    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return self
     */
    public function add(string $key, $value): self
    {
        $this->data = array_merge($this->data, [$key => $value]);
        return $this;
    }


    /**
     * Merge string to json array
     * @param string $key   Set array key
     * @param mixed $value Set array value
     * @return array
     */
    public function item(...$args): array
    {
        $key = null;
        if (isset($args[0]) && !is_array($args[0])) {
            $key = array_shift($args);
            if (count($args) === 1) {
                $args = $args[0];
            }
        }
        $argumnets = (!is_null($key)) ? [[$key => $args]] : $args;
        $this->data = array_merge($this->data, $argumnets);
        return reset($argumnets);
    }

    /**
     * Merge string to json array
     * @param string|array $key   Set array key
     * @param array $args Set array value
     * @return self
     */
    public function field(string|array $key, array $args): self
    {
        if (is_array($key) && count($key) > 0) {
            $key = key($key);

            if (!is_string($key)) {
                throw new \Exception("The key need to be string value", 1);
            }

            $this->fields = array_merge($this->fields, [$key => [
                "type" => $key,
                ...$args
            ]]);
        } else {
            if (!is_string($key)) {
                throw new \Exception("The key need to be string value", 1);
            }
            $this->fields = array_merge($this->fields, [$key => $args]);
        }
        return $this;
    }

    public function form($fields): self
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    /**
     * Reset
     * @return void
     */
    public function reset(?array $new = null): void
    {
        if (is_null($new)) {
            $new = array("status" => 0, "error" => 0);
        }

        $this->data = $new;
    }

    /**
     * same as @data method
     * @return mixed
     */
    public function get(?string $key = null)
    {
        return $this->data($key);
    }

    /**
     * Get current added json array data
     * @return mixed
     */
    public function data(?string $key = null)
    {
        if (!is_null($key)) {
            return $this->select($key);
        }
        return $this->data;
    }

    /**
     * Get data has HTML friendly string
     * @param  string $key select key (you can use comma sep. to traverse array)
     * @return string
     */
    public function output(string $key): ?string
    {
        $arr = $this->select($key);
        if ($get = self::encodeData($arr)) {
            return htmlentities($get);
        }
        return null;
    }

    /**
     * Convert json array to json string
     * @param  int  $options    Bitmask
     * @param  int  $depth       Set the maximum depth. Must be greater than zero
     * @return string|null        (bool if could not load json data)
     */
    public function encode(int $options = JSON_UNESCAPED_UNICODE, int $depth = 512): ?string
    {
        return self::encodeData($this->data, $options, $depth);
    }

    /**
     * Decode json data
     * @param  string  $json        Json data
     * @param  boolean $assoc       When TRUE, returned objects will be converted into associative arrays.
     * @return object|array|false   Resturns as array or false if error occoured.
     */
    public function decode($json, $assoc = true): object|array|false
    {
        if ($array = json_decode($json, $assoc)) {
            return $array;
        }
        return false;
    }

    /**
     * Validate output
     * @return void
     */
    public function validate(): void
    {
        $error = (static::ERROR_MESSAGES[self::error()] ?? null);
        if (!is_null($error)) {
            throw new \Exception($error, self::error());
        }
        //throw new \Exception('An unexpected Json error has occurred', self::error());
    }

    /**
     * Travers slect data
     * @param  string $key
     * @return mixed
     */
    private function select(string $key)
    {
        $set = $this->data;
        $exp = explode(",", $key);
        foreach ($exp as $key) {
            if (isset($set[$key])) {
                $set = $set[$key];
            } else {
                return null;
            }
        }
        return $set;
    }

    /**
     * Json encode data
     * @param  array    $json   array to json
     * @param  int      $flag   read php.net (or use the default)
     * @param  int      $depth  read php.net
     * @return string|null
     */
    final protected static function encodeData(array $json, int $flag = JSON_UNESCAPED_UNICODE, int $depth = 512): ?string
    {
        if (!($depth > 0 && $depth <= 2147483647)) {
            throw new \Exception("The json encode depth need to be min 1 and max 2147483647!", 1);
        }
        if (count($json) > 0 && ($encode = json_encode($json, $flag, $depth))) {
            return $encode;
        }
        return null;
    }

    /**
     * Get last json error
     * @return int
     */
    final protected static function error(): int
    {
        return json_last_error();
    }
}
