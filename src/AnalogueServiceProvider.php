<?php namespace Analogue\ORM;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Proxy\ProxyCache;
use Analogue\ORM\Plugins\Timestamps\TimestampsPlugin;
use Analogue\ORM\Plugins\SoftDeletes\SoftDeletesPlugin;

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

		$manager->registerPlugin(new TimestampsPlugin);
		$manager->registerPlugin(new SoftDeletesPlugin);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('analogue', function ($app) {

			$db = new DatabaseManager($app, $app['db.factory']);

			$event = $app->make('events');

			return new Manager($db, $event);
		});
	}
	
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('analogue');
	}

}
