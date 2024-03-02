<?php

namespace MaplePHP\Foundation\Cli;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Stream;
use MaplePHP\Http\Client;
use MaplePHP\Http\Request;
use MaplePHP\Http\UploadedFile;
use MaplePHP\Validate\Inp;
use Exception;

/**
 * Is used to make the development process so much easier.
 * @psalm-suppress ForbiddenCode
 */
class StandardInput
{
    private $stream;
    private $response;
    private $jsonFileStream;
    private $jsonFileStreamFile;

    public function __construct(ResponseInterface $response)
    {
        $this->stream = new Stream(Stream::STDIN, "r");
        $this->response = $response;
    }

    /**
     * CLi confirmation
     * @param  string   $message
     * @param  callable $call 
     * @return void
     */
    public function confirm(string $message, callable $call)
    {
        $this->write($message);
        $this->write("Type 'yes' to continue: ", false);
        if (strtolower($this->stream->getLine()) !== "yes") {
            $this->write("Aborting");
        } else {
            $this->write("...\n");
            $call($this->stream);
        }
    }

    /**
     * Will create steps
     * @param  string      $message
     * @param  string|null $default
     * @param  string|null $response
     */
    public function step(?string $message, ?string $default = null, ?string $response = null)
    {
        if (is_string($default) && strlen($default)) {
            $message .= " (Default value \"{$default}\")";
        }
        $message .= ": ";
        $this->write($message, false);
        $getLine = $this->stream->getLine();
        if ($response) {
            $this->write($response);
        }
        return ($getLine !== "" ? $getLine : $default);
    }

    /**
     * Will mask out ouput stream
     * @param  string|null $prompt
     * @return string
     */
    function maskedInput(?string $prompt = null, ?string $valid = "required", array $args = []): string
    {
        $this->stream = new Stream(Stream::STDIN, "r");
        $prompt = $this->prompt($prompt." (masked input)");

        if(function_exists("shell_exec")) {
            $this->stream->write($prompt);
            // Mask input
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Not yet tested. But should work if my research is right
                $input = rtrim((string)shell_exec("powershell -Command \$input = Read-Host -AsSecureString; [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR(\$input))"));
            } else {
                // Tested and works
                $input = rtrim((string)shell_exec('stty -echo; read input; stty echo; echo $input'));
            }

            if(!$this->validate($input, $valid, $args)) {
                $this->stream->write(PHP_EOL."Input is requied. Try again!".PHP_EOL);
                $input = $this->maskedInput($prompt, $valid, $args);
            }

        } else {
            $input = $this->step("Warning: The input will not be mask. Your server do not support the \"shell_exec\" function. MaplePHP is using shell_exec to mask the input.\n\nPress Enter to continue");
            $this->required($prompt, $valid, $args);
        }

        $this->stream->write(PHP_EOL);
        return $input;
    }

    /**
     * Will give you multiple option to choose between 
     * @param  array  $choises
     * @return string
     */
    public function chooseInput(array $choises, ?string $prompt = null): string
    {
        if(count($choises) === 0) {
            throw new Exception("Arg1 choises is an empty array!", 1);
        }

        $keys = array_keys($choises);
        $firstOpt = reset($keys);
        $lastOpt = end($keys);

        $out = $this->prompt($prompt, "Choose input")."\n";
        foreach($choises as $key => $value) {
            $out .= "{$key}: {$value}\n";
        }
        $this->write($out);

        $message = "Choose input between ({$firstOpt}-{$lastOpt})";
        if($firstOpt === $lastOpt) {
            $message = "You can at the moment only choose ({$firstOpt})";
        }
        $value = $this->required($message);
        if(!isset($choises[$value])) {
            $value = $this->chooseInput($choises);
        }
        return $value;
    }

    /**
     * Will make input required
     * @param  string|null $message
     * @return string
     */
    public function required(?string $message, ?string $valid = "required", array $args = []): string
    {
        $line = $this->step($message);
        if(!$this->validate((string)$line, $valid, $args)) {
            $line = $this->required($message, $valid, $args);
        }
        return $line;
    }


    /**
     * Validate input before continue
     * @param  string $value Input value
     * @param  string $valid A validation method from Maple Validate class
     * @param  array  $args  Possible validate arguments to pass to the validation class
     * @return bool
     */
    protected function validate(string $value, ?string $valid = "required", array $args = [])
    {
        if(is_null($valid)) {
            return true;
        }
        $inp = new Inp($value);
        if(!method_exists($inp, $valid)) {
            throw new Exception("The validation do not exists", 1);
        }
        return call_user_func_array([$inp, $valid], $args);
    }


    /**
     * Dispatch cli
     * @param  array  $data
     * @return null|string
     */
    public function dispatcher(array $data): ?string
    {
        $value = null;
        $default = ($data['default'] ?? null);
        $prompt = ($data['prompt'] ?? null);
        $validateOpt = ($data['validate'] ?? null);
        $validate = !is_null($validateOpt) ? key($validateOpt) : null;
        $args = ($validateOpt[$validate] ?? []);

        switch(($data['type'] ?? "")) {
            case 'masked':
                $value = $this->maskedInput($prompt, $validate, $args);
                break;
            default:
                $value = $this->step($prompt, $default);
                if(!$this->validate((string)$value, $validate, $args)) {
                    $value = $this->dispatcher($data);
                }
                break;
        }

        return $value;
    }

    /**
     * Write to stream
     * @param  string       $message
     * @param  bool|boolean $lineBreak
     * @return void
     */
    public function write(string $message, bool $lineBreak = true): void
    {
        if ($lineBreak) {
            $message = "{$message}\n";
        }
        $this->stream->write($message);
    }

    /**
     * Create file with stream
     * @param  string $content
     * @param  string $file
     * @return void
     */
    public function createFile(string $content, string $file)
    {
        $envStream = new Stream(Stream::TEMP);
        $envStream->write($content);
        $upload = new UploadedFile($envStream);
        $upload->moveTo($file);
    }

    /**
     * Read file from stream
     * @param  string $file
     * @return string
     */
    public function readFile(string $file): string
    {
        $stream = new Stream($file);
        return $stream->getContents();
    }

    /**
     * Get json from stream 
     * @param string $file
     */
    public function setJsonFileStream(string $file)
    {
        if (is_null($this->jsonFileStream)) {
            $this->jsonFileStreamFile = $file;
            $this->jsonFileStream = false;
            if (is_file($file)) {
                $data = $this->readFile($file);
                $this->jsonFileStream = json_decode($data, true);
            }
        }
        return $this->jsonFileStream;
    }

    /**
     * Extract Remote IP with help of shell_exec and curl
     * Cli command do not have access to remote IP, so this is a next best solution.
     * This is just a lossy function and will just try to fetch remote IP remote.
     * Does the script take more than 3 sec then it will abort
     * WARNING: Built to wokr with CLI and not server
     * @return string|false
     */
    function lossyGetPublicIP(): string|false
    {
        if (extension_loaded('curl')) {
            $client = new Client([
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT => 3
            ]);
            $request = new Request("GET", "http://ipecho.net/plain");
            $response = $client->sendRequest($request);
            $publicIP = $response->getBody()->getContents();
            if (filter_var($publicIP, FILTER_VALIDATE_IP)) {
                return $publicIP;
            }
        }
        return false;
    }

    /**
     * Will help cli promp to get a lossy idea what kind of root directory it has
     * It is not 100% accurate! function is just used to make the install process more pedagogical!
     * WARNING: Built to wokr with CLI and not server
     * @param  string $fullDirPath E.g. '/var/www/html/dir1/dir2'
     * @return string|false
     */
    function lossyGetRelativePath($fullDirPath): string|false
    {
        $checkDirs = [
            '/var/www/html', 
            '/Library/WebServer/Documents', 
            'C:\xampp\htdocs', 
            '/opt/lampp/htdocs', 
            '/usr/share/nginx/html', 
            'C:\nginx\html'
        ];
        foreach($checkDirs as $basePath) {
            if(strpos($fullDirPath, $basePath) === 0) {
                return substr($fullDirPath, strlen($basePath));
            }
        }
        return "";
    }


    /**
     * Lossy get local ip E.g. 127.0.0.1 or 127.0.1.1
     * WARNING: Built to wokr with CLI and not server
     * @return string
     */
    function lossyGetLocalIP(): string
    {
        $serverIP = "";
        $host = gethostname();
        if(is_string($host)) {
            $serverIP = gethostbyname($host);
        }
        if(strlen($serverIP) === 0) {
            $serverIP = "127.0.0.1";
        }
        return $serverIP;
    }

    /**
     * Get json data
     */
    public function getJsonData()
    {
        return $this->jsonFileStream;
    }

    public function jsonToFile(array $array, ?callable $call = null)
    {

        if (is_null($this->jsonFileStream)) {
            throw new Exception("You need to set @setJsonFileStream([FILE_PATH]) first!", 1);
        }

        $insert = $array;
        if (is_file($this->jsonFileStreamFile)) {
            if ($data = $this->jsonFileStream) {
                if (!is_null($call)) {
                    $insert = $call($data, $array);
                    if (!is_array($insert)) {
                        throw new Exception("Arg 3 (callable) Needs to return an array", 1);
                    }
                } else {
                    $insert = array_merge($data, $array);
                }
            }
        }

        $this->createFile(json_encode($insert, JSON_PRETTY_PRINT), $this->jsonFileStreamFile);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response->withBody($this->stream);
    }

    public function getConfig(): array
    {
        return (array)$_ENV['config'];
    }

    /**
     * Create prompt
     * @param  string|null $prompt
     * @param  string      $default
     * @return string
     */
    protected function prompt(?string $prompt = null, string $default = "Input your value"): string
    {
        $prompt = (is_null($prompt) ? $default : $prompt);
        return rtrim($prompt, ":").":";
    }
}
