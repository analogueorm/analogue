<?php

use DB;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;
use Analogue\Factory\Factory;
use Faker\Factory as Faker;

abstract class AnalogueTestCase extends Illuminate\Foundation\Testing\TestCase
{
    use InteractsWithDatabase;

    protected $analogue;

    protected $usedIds = [];

    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->app->singleton(Factory::class, function ($app) {
            $faker = Faker::create();
            $analogueManager = $app->make('analogue');
            return Factory::construct($faker, __DIR__.'/factories', $analogueManager);
        });

        $this->analogue = $this->app->make('analogue');
        $this->analogue->setStrictMode(true);

        $this->migrateDatabase();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(\Analogue\ORM\AnalogueServiceProvider::class);
        $app->register(\Analogue\Factory\FactoryServiceProvider::class);
        
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
   
    /**
     * Run Migrations
     * 
     * @return void
     */
    protected function migrateDatabase()
    {
        $migrationPaths = [
            __DIR__ . "/migrations",
        ];

        foreach($migrationPaths as $path) {
            $this->migrateDatabaseFromPath($path);
        }
    }

    /**
     * Run all database migrations from the specified path
     * 
     * @param  string $path
     * @return void
     */
    protected function migrateDatabaseFromPath($path)
    {
        $fileSystem = new Filesystem;
        $classFinder = new ClassFinder;

        foreach ($fileSystem->files($path) as $file) {
            
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->up();
        }
    }

    /**
     * Get the mapper for a specific entity
     * 
     * @param  mixed $entity
     * @return Mapper
     */
    protected function mapper($entity) 
    {
        return $this->analogue->mapper($entity);
    }

    /**
     * Get the factory for a specific entity
     * 
     * @param  mixed $entityClass 
     * @return FactoryBuilder
     */
    protected function factory($entityClass) 
    {
        return analogue_factory($entityClass);
    }

    /**
     * Build an entity object
     * 
     * @param  string $entityClass 
     * @param  array  $attributes  
     * @return mixed  
     */
    protected function factoryMake($entityClass, array $attributes = [] )
    {
        return $this->factory($entityClass)->make($attributes);
    }   

     /**
     * Build an entity object with a random ID
     * 
     * @param  string $entityClass 
     * @param  array  $attributes  
     * @return mixed  
     */
    protected function factoryMakeUid($entityClass, array $attributes = [] )
    {
        $attributes['id'] = $this->randId();
        return $this->factory($entityClass)->make($attributes);
    }   

    /**
     * Create an entity object and persist it in database
     * 
     * @param  string $entityClass 
     * @param  array  $attributes  
     * @return mixed  
     */
    protected function factoryCreate($entityClass, array $attributes = [] )
    {
        return $this->factory($entityClass)->create($attributes);
    }

    /**
     * Create an entity object with a random ID and persist it in database
     * 
     * @param  string $entityClass 
     * @param  array  $attributes  
     * @return mixed
     */
    protected function factoryCreateUid($entityClass, array $attributes = [] )
    {
        $attributes['id'] = $this->randId();
        return $this->factory($entityClass)->create($attributes);
    }

    /**
     * Return Faker Factory
     * 
     * @return Faker
     */
    protected function faker()
    {
        return Faker::create();
    }

    /**
     * Generate a random integer
     * 
     * @return integer
     */
    protected function randId()
    {
        do {
            $id = mt_rand(1,10000000);
        }
        while(in_array($id, $this->usedIds));

        return $id;
    }

    /**
     * Run a raw insert statement
     * 
     * @param  string $table
     * @param  array  $columns
     * @return integer
     */
    protected function rawInsert($table, array $columns)
    {
        return DB::table($table)->insertGetId($columns);
    }


}   


