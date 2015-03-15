# Analogue ORM 

Analogue is an easy-to-use Data Mapper for PHP. It provides a fast and intuitive way to persist your custom domain object into a SQL Database. It allows you to (almost) forget about your database implementation and think in term of object and collections instead. 

The project started as a fork from Eloquent ORM, and evolved into a fully decoupled design, that sits on top of the very robust Laravel Database component. That said, Analogue is able to peacefuly coexists with its cousin in a same application (only limitation is you cannot have relationships between the two, which is the common moraly accepted behaviour in the same family).

If you're already familiar with Eloquent, a lot of the syntax is similar, so you should be up and running in no time. In fact, you may probably gain time as Analogue leverage some common tasks as creating repository for your models, or synchronizing complex relationships. 

Jump to the CodeWiki for some examples.

## Changelog 

### Version 2.1

- Now support POPO's that implements Mappable interface.
- Introducing a MappableTrait for quick implementation. 
- Store/Delete methods now accept a Collection as argument.
- EntityMap are now autodected when in the same namespace as the entity.

### Version 2.0

- Laravel 5 Support.


## Documentation

Wiki

## Licence

This package is licensed under the MIT License.

