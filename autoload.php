<?php
/**
 * @var string  $dir (Access from index or cli)
 */

require_once(realpath(dirname(__FILE__) . '/')."/Pollyfill/functions.php");
require_once("{$dir}vendor/autoload.php");

$autoLoadPrefixes = ["MaplePHP"];
$autoLoadPrefixesReplace = ["/", ""] + array_fill(0, count($autoLoadPrefixes), "");
$autoLoadPrefixes = ["\\", "MaplePHP/"] + $autoLoadPrefixes;
spl_autoload_register(function ($className) use ($dir, $autoLoadPrefixes, $autoLoadPrefixesReplace) {
    $className = str_replace($autoLoadPrefixes, $autoLoadPrefixesReplace, $className);
    $classFilePath = "{$dir}app/Libraries/$className.php";
    if (!is_file($classFilePath)) {
        $classFilePath = "{$dir}app/$className.php";
    }
    if(!is_file($classFilePath)) {
        $classFilePath = "$dir$className.php";
    }
    if (is_file($classFilePath)) {
        require_once($classFilePath);
    }
});
