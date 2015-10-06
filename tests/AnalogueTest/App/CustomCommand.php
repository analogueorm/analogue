<?php namespace AnalogueTest\App;

use Analogue\ORM\Commands\Command;
use Analogue\ORM\Entity;

class CustomCommand extends Command
{

    public function execute()
    {
        return 'executed';
    }
}
