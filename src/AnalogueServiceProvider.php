<?php

namespace Analogue\ORM;

use Analogue\ORM\Drivers\IlluminateConnectionProvider;
use Analogue\ORM\Drivers\IlluminateDriver;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\System\Manager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;

/**
 * Integrate Analogue into Laravel.
 */
class AnalogueServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Experimenting autoloading proxies

        $this->app->singleton('analogue', function ($app) {
            $db = $app['db'];

            $connectionProvider = new IlluminateConnectionProvider($db);

            $illuminate = new IlluminateDriver($connectionProvider);

            $driverManager = new DriverManager();

            $driverManager->addDriver($illuminate);

            $event = $app->make('events');

            $manager = new Manager($driverManager, $event);

            $manager->registerPlugin(\Analogue\ORM\Plugins\Timestamps\TimestampsPlugin::class);
            $manager->registerPlugin(\Analogue\ORM\Plugins\SoftDeletes\SoftDeletesPlugin::class);

            // If the cache is pre laravel 5.5, it doesn't implements PSR-16, so we'll skip it.
            $cache = $app->make(CacheRepository::class);

            if ($cache instanceof CacheInterface) {
                $manager->setCache($cache);
            }

            $proxyPath = storage_path('framework/analogue/proxies');

            if (!file_exists($proxyPath)) {
                mkdir($proxyPath, 0777, true);
            }

            $proxyConfig = new \ProxyManager\Configuration();
            $proxyConfig->setProxiesTargetDir($proxyPath);
            spl_autoload_register($proxyConfig->getProxyAutoloader());

            $manager->setProxyPath($proxyPath);

            return $manager;
        });

        $this->app->bind(Manager::class, function ($app) {
            return $app->make('analogue');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['analogue'];
    }
}
