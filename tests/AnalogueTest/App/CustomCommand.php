<?php

namespace AnalogueTest\App;

use Analogue\ORM\Commands\Command;

class CustomCommand extends Command
{
    public function execute()
    {
        return 'executed';
    }
}
