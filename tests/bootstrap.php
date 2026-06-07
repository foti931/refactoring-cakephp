<?php
declare(strict_types=1);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}
if (!defined('APP_DIR')) {
    define('APP_DIR', 'src');
}
if (!defined('APP')) {
    define('APP', ROOT . DS . APP_DIR . DS);
}
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DS . 'config' . DS);
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
}
if (!defined('TESTS')) {
    define('TESTS', ROOT . DS . 'tests' . DS);
}
if (!defined('TMP')) {
    define('TMP', ROOT . DS . 'tmp' . DS);
}
if (!defined('LOGS')) {
    define('LOGS', ROOT . DS . 'logs' . DS);
}
if (!defined('RESOURCES')) {
    define('RESOURCES', ROOT . DS . 'resources' . DS);
}
if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
}
if (!defined('CAKE')) {
    define('CAKE', CORE_PATH . 'src' . DS);
}

require CONFIG . 'bootstrap.php';
