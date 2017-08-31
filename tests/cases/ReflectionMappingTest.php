<?php

use TestApp\Movie;
use TestApp\Realisator;

class ReflectionMappingTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_a_plain_php_object()
    {
        $movie = new Movie('Captain Fantastic');
        $mapper = $this->mapper($movie);
        $mapper->store($movie);
        $this->seeInDatabase('movies', [
            'title' => 'Captain Fantastic',
        ]);
    }

    /** @test */
    public function we_can_store_a_plain_php_object_with_a_belongs_to_relationship()
    {
        $realisator = new Realisator('Stanley Kubrick');
        $movie = new Movie('2001, a space odissey');
        $movie->setRealisator($realisator);
        $mapper = $this->mapper($movie);
        $mapper->store($movie);
        $this->seeInDatabase('realisators', [
            'name' => 'Stanley Kubrick',
        ]);
        $this->seeInDatabase('movies', [
            'title'         => '2001, a space odissey',
            'realisator_id' => $realisator->id,
        ]);
    }

    /** @test */
    public function we_can_store_a_plain_php_object_with_a_has_many_relationship()
    {
        $realisator = new Realisator('Stanley Kubrick');
        $movie = new Movie('2001, a space odissey');
        $realisator->addMovie($movie);
        $mapper = $this->mapper($realisator);
        $mapper->store($realisator);
        $this->seeInDatabase('realisators', [
            'name' => 'Stanley Kubrick',
        ]);
        $this->seeInDatabase('movies', [
            'title'         => '2001, a space odissey',
            'realisator_id' => $realisator->id,
        ]);
        $movie = new Movie('A clockwork orange');
        $realisator->addMovie($movie);
        $mapper->store($realisator);
        $this->seeInDatabase('movies', [
            'title'         => '2001, a space odissey',
            'realisator_id' => $realisator->id,
        ]);
        $this->seeInDatabase('movies', [
            'title'         => 'A clockwork orange',
            'realisator_id' => $realisator->id,
        ]);
    }
}
