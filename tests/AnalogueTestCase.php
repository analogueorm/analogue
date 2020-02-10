<?php

use Analogue\Factory\Factory;
use Faker\Factory as Faker;
use Illuminate\Filesystem\Filesystem;
use Laravel\BrowserKitTesting\Concerns\InteractsWithDatabase;

abstract class AnalogueTestCase extends Illuminate\Foundation\Testing\TestCase
{
    use InteractsWithDatabase;

    protected $useSqlite = true;

    protected $testDbName = 'analogue_test_db';

    protected $analogue;

    protected $usedIds = [];

    public function setUp()
    {
        parent::setUp();

        if ($this->useSqlite) {
            $this->setupSqlite();
        } else {
            $this->setupMysql();
        }

        $this->app->singleton(Factory::class, function ($app) {
            $faker = Faker::create();
            $analogueManager = $app->make('analogue');

            return Factory::construct($faker, __DIR__.'/factories', $analogueManager);
        });

        $this->analogue = $this->app->make('analogue');
        $this->analogue->setCache(Cache::driver('file'));
        $this->analogue->setStrictMode(true);

        $this->artisan('cache:clear');

        $this->migrateDatabase();
    }

    protected function setupSqlite()
    {
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setupMysql()
    {
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql.username', 'root');
        $this->app['config']->set('database.connections.mysql.database', $this->testDbName);
        $this->app['config']->set('database.connections.mysql.charset', 'utf8');
        $this->app['config']->set('database.connections.mysql.collation', 'utf8_unicode_ci');

        Schema::defaultStringLength(191);

        $this->createTestDatabase();
    }

    protected function createTestDatabase()
    {
        $conn = new PDO('mysql:host=localhost', 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = 'DROP DATABASE IF EXISTS '.$this->testDbName;
        $conn->exec($sql);
        $sql = 'CREATE DATABASE IF NOT EXISTS '.$this->testDbName;
        $conn->exec($sql);
        $conn = null;
    }

    protected function dropTestDatabase()
    {
        $conn = new PDO('mysql:host=localhost', 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = 'DROP DATABASE '.$this->testDbName;
        $conn->exec($sql);
        $conn = null;
    }

    public function tearDown()
    {
        if (!$this->useSqlite) {
            $this->dropTestDatabase();
        }

        parent::tearDown();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(\Analogue\ORM\AnalogueServiceProvider::class);
        $app->register(\Analogue\Factory\FactoryServiceProvider::class);

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Run Migrations.
     *
     * @return void
     */
    protected function migrateDatabase()
    {
        $migrationPaths = [
            __DIR__.'/migrations',
        ];

        foreach ($migrationPaths as $path) {
            $this->migrateDatabaseFromPath($path);
        }
    }

    /**
     * Run all database migrations from the specified path.
     *
     * @param string $path
     *
     * @return void
     */
    protected function migrateDatabaseFromPath($path)
    {
        $fileSystem = new Filesystem();
        $classFinder = new ClassFinder();

        foreach ($fileSystem->files($path) as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass())->up();
        }
    }

    protected function db()
    {
        return $this->app->make('db');
    }

    /**
     * Get the mapper for a specific entity.
     *
     * @param mixed $entity
     *
     * @return Mapper
     */
    protected function mapper($entity)
    {
        return $this->analogue->mapper($entity);
    }

    /**
     * Get the factory for a specific entity.
     *
     * @param mixed $entityClass
     *
     * @return FactoryBuilder
     */
    protected function factory($entityClass)
    {
        return analogue_factory($entityClass);
    }

    /**
     * Build an entity object.
     *
     * @param string $entityClass
     * @param array  $attributes
     *
     * @return mixed
     */
    protected function factoryMake($entityClass, array $attributes = [])
    {
        return $this->factory($entityClass)->make($attributes);
    }

    /**
     * Build an entity object with a random ID.
     *
     * @param string $entityClass
     * @param array  $attributes
     *
     * @return mixed
     */
    protected function factoryMakeUid($entityClass, array $attributes = [])
    {
        $attributes['id'] = $this->randId();

        return $this->factory($entityClass)->make($attributes);
    }

    /**
     * Create an entity object and persist it in database.
     *
     * @param string $entityClass
     * @param array  $attributes
     *
     * @return mixed
     */
    protected function factoryCreate($entityClass, array $attributes = [])
    {
        return $this->factory($entityClass)->create($attributes);
    }

    /**
     * Create an entity object with a random ID and persist it in database.
     *
     * @param string $entityClass
     * @param array  $attributes
     *
     * @return mixed
     */
    protected function factoryCreateUid($entityClass, array $attributes = [])
    {
        $attributes['id'] = $this->randId();

        return $this->factory($entityClass)->create($attributes);
    }

    /**
     * Return Faker Factory.
     *
     * @return Faker
     */
    protected function faker()
    {
        return Faker::create();
    }

    /**
     * Generate a random integer.
     *
     * @return int
     */
    protected function randId()
    {
        do {
            $id = mt_rand(1, 10000000);
        } while (in_array($id, $this->usedIds));

        return $id;
    }

    /**
     * Run a raw insert statement.
     *
     * @param string $table
     * @param array  $columns
     *
     * @return int
     */
    protected function rawInsert($table, array $columns)
    {
        return DB::table($table)->insertGetId($columns);
    }

    /**
     * Dump events, for debugging purpose.
     *
     * @return void
     */
    protected function logEvents()
    {
        $events = ['store',
            'stored',
            'creating',
            'created',
            'updating',
            'updated',
            'deleting',
            'deleted',
        ];

        foreach ($events as $event) {
            $this->analogue->registerGlobalEvent($event, function ($entity) use ($event) {
                dump(get_class($entity).' '.$event);
            });
        }
    }

    /**
     * Migrate a DB.
     *
     * @param callable $callback
     *
     * @return void
     */
    protected function migrate($table, $callback)
    {
        Schema::create($table, $callback);
    }

    /**
     * Clear analogue's instance & db cache.
     *
     * @return void
     */
    protected function clearCache()
    {
        app('analogue')->clearCache();
    }

    /**
     * Log all queries.
     *
     * @return void
     */
    protected function logQueries()
    {
        $db = $this->app->make('db');
        $db->listen(function ($query) {
            dump($query->sql);
        });
    }
}
