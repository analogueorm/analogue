<?php

// Test debug helper
if (!function_exists('tdd')) {
    $tdd_status = false;

    function tdd($expression)
    {
        global $tdd_status;
        if ($tdd_status) {
            dd($expression);
        }
    }

    function setTddOn()
    {
        global $tdd_status;
        $tdd_status = true;
    }

    function setTddOff()
    {
        global $tdd_status;
        $tdd_status = false;
    }
}
