<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TestApp\Identity;

$factory->define(TestApp\User::class, function (Faker\Generator $faker) {
    $identity = new Identity($faker->firstname, $faker->lastname);

    return [
        'name'           => $faker->name,
        'email'          => $faker->email,
        'identity'       => $identity,
        'groups'         => new Collection(),
        'password'       => bcrypt(Str::random(10)),
        'remember_token' => Str::random(10),
    ];
});

$factory->define(TestApp\Blog::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
    ];
});

$factory->define(TestApp\Article::class, function (Faker\Generator $faker) {
    return [
        'title'   => $faker->sentence,
        'slug'    => $faker->slug,
        'content' => $faker->paragraph,
    ];
});

$factory->define(TestApp\Group::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->sentence,
    ];
});
