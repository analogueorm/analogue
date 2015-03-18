# Analogue ORM [![Build Status](https://travis-ci.org/analogueorm/analogue.svg)](https://travis-ci.org/analogueorm/analogue)

**Analogue** is an easy-to-use **Data Mapper ORM** for **PHP**. It provides a fast and intuitive way to persist your custom domain object into a SQL Database. It allows you to (almost) forget about your database implementation and think in term of object and collections instead. 

The project started as a fork from **Eloquent ORM** by *Taylor Otwell*, and evolved into a fully **SOLID** design, that sits on top of the very robust **Laravel Database** component. That said, **Analogue** is able to peacefuly coexists with its cousin in a same application *(only limitation is you cannot have relationships between the two, which is a common moraly accepted behaviour in the same family...)*

If you're already familiar with Eloquent, a lot of the syntax is similar, so you should be up and running in no time. In fact, you may probably **gain time** as Analogue leverage some heavy DB tasks as **synchronizing complex relationships**., letting you think in term of **objects** and **collections** instead.

```php

$files = $filesystem->files('/path/to/gallery');

$gallery = new Gallery('Trip to India');

foreach($files as $file)
{
    $photo = new Photo($file);

    $gallery->addPhoto($photo);
}

$mapper->store($gallery);

```

Jump to the [documentation](https://github.com/analogueorm/analogue/wiki) for more examples.

## Changelog 

### Version 2.1

- Package is now framework agnostic.
- Now support any plain object that implements Mappable interface.
- Introducing a MappableTrait for quick implementation. 
- Store/Delete methods now accept a array and collections as argument.
- EntityMap are now autodected when in the same namespace as the entity.

### Version 2.0

- Laravel 5 Support.

## Licence

This package is licensed under the MIT License.

