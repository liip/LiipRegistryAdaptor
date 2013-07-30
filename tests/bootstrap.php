<?php

if (!defined('WATCHDOG_NOTICE')) {
    define('WATCHDOG_NOTICE', 5);
}

$baseDir =  dirname(__DIR__);

if (file_exists($baseDir . "/vendor/autoload.php")) {

    $loader = require_once $baseDir . "/vendor/autoload.php";

} else {
    die(
        "\n[ERROR] You need to run composer before running the test suite.\n".
            "To do so run the following commands:\n".
            "    curl -s http://getcomposer.org/installer | php\n".
            "    php composer.phar install --dev\n\n"
    );
}

$loader->addClassMap(
    array(
        "Liip\\Registry\\Adaptor\\Tests\\RegistryTestCase" => $baseDir . '/tests/Liip/Registry/Adaptor/RegistryTestCase.php'
    )
);
