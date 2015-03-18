<?php
ini_set('error_reporting', E_ALL); 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$autoload = require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
$autoload->add('AnalogueTest', __DIR__);

// Date setup
date_default_timezone_set('Europe/Berlin');

// Some shortcut function
function get_analogue()
{
    $testDb = [
        'driver'   => 'sqlite',
        'database' => __DIR__.'/test.sqlite',
        'prefix'   => '',
    ];

    return new Analogue\ORM\Analogue($testDb);
}

function get_mapper($entity)
{
    return get_analogue()->mapper($entity);
}

function tdd($value)
{
    echo var_dump($value);
    die;
}