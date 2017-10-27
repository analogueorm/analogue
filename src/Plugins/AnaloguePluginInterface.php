<?php

namespace Analogue\ORM\Plugins;

interface AnaloguePluginInterface
{
    /**
     * Boot the plugin.
     *
     * @return void
     */
    public function register();

    /**
     * Get custom events provided by the plugin.
     *
     * @return array
     */
    public function getCustomEvents(): array;
}
