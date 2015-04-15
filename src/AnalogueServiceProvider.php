<?php namespace Analogue\ORM;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\Drivers\IlluminateDriver;
use Analogue\ORM\Drivers\IlluminateConnectionProvider;

/**
 * Integrate Analogue into Laravel
 */
class AnalogueServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	public function boot()
	{
		$manager = $this->app->make('analogue');

		$manager->registerPlugin('Analogue\ORM\Plugins\Timestamps\TimestampsPlugin');
		$manager->registerPlugin('Analogue\ORM\Plugins\SoftDeletes\SoftDeletesPlugin');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('analogue', function ($app) {

			$db = $app['db'];

			$connectionProvider = new IlluminateConnectionProvider($db);

			$illuminate = new IlluminateDriver($connectionProvider);

			$driverManager = new DriverManager;

			$driverManager->addDriver($illuminate);

			$event = $app->make('events');

			return new Manager($driverManager, $event);
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
