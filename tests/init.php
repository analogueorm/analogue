<?php
ini_set('error_reporting', E_ALL); 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$autoload = require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
$autoload->add('AnalogueTest', __DIR__);

// Date setup
date_default_timezone_set('Europe/Berlin');

// Copy DB Template to a temp db
copy(__DIR__.'/test.sqlite', __DIR__.'/temp.sqlite');
// Copy DB On second file for testing multiple connections
copy(__DIR__.'/test.sqlite', __DIR__.'/external.sqlite');

// Some shortcut function
function get_analogue()
{
    $testDb = [
        'driver'   => 'sqlite',
        'database' => __DIR__.'/temp.sqlite',
        'prefix'   => '',
    ];

    $externalDb = [
        'driver'   => 'sqlite',
        'database' => __DIR__.'/external.sqlite',
        'prefix'   => '',
    ];


    $analogue = new Analogue\ORM\Analogue($testDb);

    $analogue->addConnection($externalDb, 'external');

    $analogue->registerPlugin('Analogue\ORM\Plugins\Timestamps\TimestampsPlugin');
    $analogue->registerPlugin('Analogue\ORM\Plugins\SoftDeletes\SoftDeletesPlugin');

    return $analogue;
}

function get_mapper($entity)
{
    return get_analogue()->mapper($entity);
}


$globalDebug = true;

function setDebugOn()
{
    global $globalDebug;
    $globalDebug = true;
}

function setDebugOff()
{
    global $globalDebug;
    $globalDebug = false;
}

function tdd($value)
{
    global $globalDebug;
    if($globalDebug)
    {
        echo var_dump($value);
        die;
    }
}
