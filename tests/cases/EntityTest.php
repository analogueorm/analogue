<?php

use TestApp\Article;
use TestApp\Blog;
use TestApp\Movie;
use TestApp\User;

class EntityTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_convert_entity_to_array()
    {
        $user = $this->factoryCreate(User::class);
        $userArray = $user->toArray();
        $this->assertEquals($user->email, $userArray['email']);
    }

    /** @test */
    public function we_cant_see_hidden_attributes_when_converting_to_array_or_json()
    {
        $user = $this->factoryMake(User::class);
        $userArray = $user->toArray();
        $userJson = json_encode($user);
        $this->assertFalse(array_key_exists('password', $userArray));
        $reencode = json_decode($userJson);
        $this->assertFalse(array_key_exists('password', $reencode));
    }

    /** @test */
    public function we_can_use_magic_setters_and_getters()
    {
        $user = $this->factoryMake(User::class);
        $user->name = 'new name';
        $this->assertEquals('new name', $user->name);
    }

    /** @test */
    public function we_can_convert_an_entity_to_json()
    {
        $user = $this->factoryMake(User::class);
        $userJson = json_encode($user);
    }

    /** @test */
    public function we_can_use_get_and_set_mutators()
    {
        $article = $this->factoryMake(Article::class);
        $article->slug = 'some article';
        $this->assertEquals('some-article', $article->slug);

        $user = $this->factoryMake(User::class, ['email' => 'Me@example.com']);
        $this->assertEquals('me@example.com', $user->email);
    }

    /** @test */
    public function all_proxies_are_created_when_mapping_an_entity_from_a_query()
    {
        $user = $this->factoryCreateUid(User::class);
        $id = $user->id;
        $mapper = $this->mapper($user);

        $this->clearCache();

        $user = $mapper->find($id);
        $this->assertInstanceOf(Analogue\ORM\System\Proxies\CollectionProxy::class, $user->groups);
        $this->assertInstanceOf(Analogue\ORM\System\Proxies\CollectionProxy::class, $user->articles);
    }

    /** @test */
    public function we_can_access_a_lazy_loaded_relationship()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $user->blog = $blog;
        $mapper = $this->mapper($user);
        $mapper->store($user);
        $id = $user->id;
        $user = $mapper->find($id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(Blog::class, $user->blog);
    }

    /** @test */
    public function we_can_access_mapped_columns()
    {
        $user = $this->factoryMake(User::class);
        $mapper = $this->mapper($user);
        $user->rememberToken = '123456';
        $user->identity->fname = 'adro';
        $mapper->store($user);
        $id = $user->id;
        $user = null;
        $user = $mapper->find($id);
        $this->assertEquals('123456', $user->rememberToken);
        $this->assertEquals('adro', $user->identity->fname);
        $user->rememberToken = '1234567';
        $mapper->store($user);
        $user = null;
        $user = $mapper->find($id);
        $this->assertEquals('1234567', $user->rememberToken);
    }

    /** @test */
    public function we_can_access_camel_case_properities()
    {
        $movie = new Movie('Analogue Tutorial');
        $mapper = $this->mapper($movie);
        $movie->setSomeText('analogue is awesome');
        $mapper->store($movie);
        $id = $movie->getId();
        $movie = null;
        $movie = $mapper->find($id);
        $this->assertEquals('analogue is awesome', $movie->getSomeText());
        $movie->setSomeText('analogue is awesome!!');
        $mapper->store($movie);
        $movie = null;
        $movie2 = $mapper->find($id);
        $this->assertEquals('analogue is awesome!!', $movie2->getSomeText());
        $this->seeInDatabase('movies', [
            'title'     => 'Analogue Tutorial',
            'some_text' => 'analogue is awesome!!',
        ]);
    }

    /** @test */
    public function we_can_access_a_lazy_loaded_collection()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $article1 = $this->factoryMakeUid(Article::class);
        $article2 = $this->factoryMakeUid(Article::class);
        $blog->articles = [$article1, $article2];
        $mapper = $this->mapper($blog);
        $mapper->store($blog);
        $this->seeInDatabase('blogs', ['id' => $blog->id]);
        $this->seeInDatabase('articles', ['id' => $article1->id]);
        $this->seeInDatabase('articles', ['id' => $article2->id]);
        $this->clearCache();
        $blog = $mapper->find($blog->id);
        $this->assertEquals(2, $blog->articles->count());
    }
}
