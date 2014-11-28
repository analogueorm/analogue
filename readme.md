# Analogue ORM

**Analogue** is a port of the laravel's *Eloquent ORM*, implemented as a Data Mapper. It aims to provide a flexible, decoupled and easy to use ORM for large application designs.

##Main Features :

* Map SQL Database to database agnostic objects.
* Store Entity as a 'root aggregate', saving related Entities on the fly. 
* Make queries using the Fluent Query Builder
* Lazy loading of relationships via Proxy objects.
* Eager Loading for solving the N+1 problem
* Timestamps support
* Soft Deletes
* Polymorphic Relationships
* Dynamic Relationships
* Flexible event system
* Out of the box Authentication Driver for Laravel.
* Easily Extendable


## Installation

Add this line to your composer.json file : 

```
"Analogue/ORM": "~1.0"
```

Then run : 

```
composer update
```

Add the Service Provider to config/app.php :

```
'Analogue\ORM\AnalogueServiceProvider',
```

If you're using facades, add this line to the aliases :
```
'Analogue' => 'Analogue\ORM\AnalogueFacade',
```

## Getting Started

### Creating Entity Object

First let's create a simple user Entity class :

```php
namespace Acme\Users;

use Analogue\ORM\Entity;

class User extends Entity {

	public function __construct($email, $password)
	{
		$this->email = $email;
		$this->password = \Hash::make($password);
	}

}
```

Analogue's Entity objects are the equivalent of Eloquent objects, with the main difference that they have no interaction with the database at all. DB Queries takes place inside of a repository object. 

### Repositories

You can either create your own repository classes by *extending the Analogue\ORM\Repository class*, or use a factory method to build a repository on the fly. Let's use the latter for now :

```php

$user = new Acme\Users\User('bob@example.com', 'MySecr3tP4ssword^&!');

$userRepository = Analogue::repository('Acme\Users\User');

$userRepository->store($user);

```

Now let's get all our users :

```
$users = $userRepository->all();
```

In fact you can build queries directly on the repository object's itself, because it's build on an underlying query builder. Some examples :

```
$user = $userRepository->find(45);

$user = $userRepository->where('email', '=', 'bob@example.com')->first();

$user = $userRepository->paginate(50);

...
```

## Full Documentation 

	(coming soon)


## Licence

	This package is licensed under the MIT License.

