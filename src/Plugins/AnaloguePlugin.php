<?php

namespace Analogue\ORM\Plugins;

use Analogue\ORM\System\Manager;

abstract class AnaloguePlugin implements AnaloguePluginInterface
{
    /**
     * Manager instance.
     *
     * @var Manager
     */
    protected $manager;

    /**
     * AnaloguePlugin constructor.
     *
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Boot the plugin.
     *
     * @return void
     */
    abstract public function register();

    /**
     * Get custom events provided by the plugin.
     *
     * @return array
     */
    abstract public function getCustomEvents();
}
