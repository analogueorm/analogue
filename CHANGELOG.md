# Changelog

#### Version 5.6
- Laravel 5.6 support
- Bring back ability to map DB columns that name are not equals to the name of the attribute.
- Add ability to map DB snake case columns to camel case properties on entities.

#### Version 5.5
- Laravel 5.5 support
- Pushed miminum requirements to PHP7
- Complete support of Plain PHP objects via reflection based hydration/dehydration
- Improved Lazy-loading proxies.
- New, more flexible Value Object implementation, that can now be defined as `embedsOne()`, `embedsMany()` relationships
- Embedded value object can now be stored as a mysql JSON field
- Analogue entities can now be instantiated using laravel's `IoC Container` or any PSR-11 compatible container. 
- Added [MongoDB](https://github.com/analogueorm/mongodb) driver.
- Package auto discovery (L5.5)

#### Version 5.4
- Illuminate 5.4 Compatibility.
- Add Abillity to map DB columns that name are not equals to the name of the attribute.

#### Version 5.3
- Illuminate 5.3 Compatibility. 
- Now fully support Single Table Inheritance.
- EntityCollection is now correctly keyed by id

#### Version 5.1
- Illuminate 5.1 + 5.2 Compatibility. 

#### Version 5.0
- Analogue version now mirrors illuminate version. 

#### Version 2.1.3
- Mutator feature in base Entity class.
- Ability to add entities to a proxy collection without lazyloading it.

### Version 2.1

- Package is now framework agnostic.
- Now support any plain object that implements Mappable interface.
- Introducing a MappableTrait for quick implementation. 
- Queries can now be run directly on the mapper Object. 
- Store/Delete methods now accept a array and collections as argument.
- EntityMap are now autodected when in the same namespace as the entity.
- Base Entity class Supports hidden attributes.
- Many workflow related improvements.

### Version 2.0

- Laravel 5 Support.

