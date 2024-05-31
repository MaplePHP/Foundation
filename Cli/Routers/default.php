<?php

/**
 * Find all, string number: [^/]+
 * IF match or else: (?:match|elseMatch)
 *
 * Can be used as bellow, this will first search for "sibling param" if not just use "/":
 *
 * {page:(?:.+/|/)vara-bilar}
 *
 * OK: "/PageParam1/PageParam2/vara-bilar"
 * OK: "/vara-bilar"
 *
 * Add a dynamic route from pages
 * /{page:.+}/{id:\d+}/{permalink:bil-[^/]+}
 *
 * @var object $routes
 */

$routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Cli', "help"]);

// Group handle is not required, but is a great way to organizing CLI packages->type calls

//
$routes->cli("/install", ['MaplePHP\Foundation\Cli\Connectors\Install', "install"]);
$routes->cli("/install/help", ['MaplePHP\Foundation\Cli\Connectors\Install', "help"]);
$routes->cli("/install/{action:[^/]+}", ['MaplePHP\Foundation\Cli\Connectors\Install', "config"]);

$routes->group("/server", function ($routes) {
    // It is recommended to add this handle at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Server', "help"]);
    $routes->cli("/start", ['MaplePHP\Foundation\Cli\Connectors\Server', "start"]);
});



// Database creation/migration
$routes->group("/migrate", function ($routes) {
    // It is recommended to add this handle at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    //$routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Migrate', "migrate"]);
    $routes->cli("[/migrate]", ['MaplePHP\Foundation\Cli\Connectors\Migrate', "migrate"]);


    $routes->cli("/read", ['MaplePHP\Foundation\Cli\Connectors\Migrate', "read"]);
    $routes->cli("/drop", ['MaplePHP\Foundation\Cli\Connectors\Migrate', "drop"]);
    $routes->cli("/help", ['MaplePHP\Foundation\Cli\Connectors\Migrate', "help"]);
});

$routes->group("/config", function ($routes) {
    // It is recommended to add this 2 handles at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Config', "help"]);

    $routes->cli("/install", ['MaplePHP\Foundation\Cli\Connectors\Config', "install"]);
    $routes->cli("/package", ['MaplePHP\Foundation\Cli\Connectors\Config', "package"]);
    $routes->cli("/create", ['MaplePHP\Foundation\Cli\Connectors\Config', "create"]);
    $routes->cli("/read", ['MaplePHP\Foundation\Cli\Connectors\Config', "read"]);
    $routes->cli("/drop", ['MaplePHP\Foundation\Cli\Connectors\Config', "drop"]);
});


$routes->group("/image", function ($routes) {
    // It is recommended to add this 2 handles at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Image', "help"]);

    $routes->cli("/resize", ['MaplePHP\Foundation\Cli\Connectors\Image', "resize"]);
});

$routes->group("/package", function ($routes) {
    // It is recommended to add this 2 handles at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Package', "help"]);

    $routes->cli("/get", ['MaplePHP\Foundation\Cli\Connectors\Package', "get"]);
    $routes->cli("/list", ['MaplePHP\Foundation\Cli\Connectors\Package', "list"]);
    $routes->cli("/inspect", ['MaplePHP\Foundation\Cli\Connectors\Package', "inspect"]);
    $routes->cli("/install", ['MaplePHP\Foundation\Cli\Connectors\Package', "install"]);
    $routes->cli("/uninstall", ['MaplePHP\Foundation\Cli\Connectors\Package', "uninstall"]);
    $routes->cli("/build", ['MaplePHP\Foundation\Cli\Connectors\Package', "build"]);
    $routes->cli("/updateBuild", ['MaplePHP\Foundation\Cli\Connectors\Package', "updateBuild"]);
    $routes->cli("/delete", ['MaplePHP\Foundation\Cli\Connectors\Package', "delete"]);
});

$routes->group("/database", function ($routes) {
    // It is recommended to add this 2 handles at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Database', "help"]);

    $routes->cli("/insertUser", ['MaplePHP\Foundation\Cli\Connectors\Database', "insertUser"]);
    $routes->cli("/insertOrg", ['MaplePHP\Foundation\Cli\Connectors\Database', "insertOrg"]);
    $routes->cli("/delete", ['MaplePHP\Foundation\Cli\Connectors\Database', "delete"]);
});

$routes->group("/mail", function ($routes) {
    // It is recommended to add this 2 handles at the begining of every grouped call
    $routes->map("*", '[/{any:.*}]', ['MaplePHP\Foundation\Cli\Connectors\Cli', "handleMissingType"]);
    $routes->cli("[/help]", ['MaplePHP\Foundation\Cli\Connectors\Mail', "help"]);
    $routes->cli("/send", ['MaplePHP\Foundation\Cli\Connectors\Mail', "send"]);
});
