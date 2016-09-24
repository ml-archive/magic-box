Laravel Magic Box [![Build Status](https://img.shields.io/travis/fuzz-productions/magic-box/master.svg?style=flat)](https://travis-ci.org/fuzz-productions/magic-box) [![Slack Status](https://fuzz-opensource.herokuapp.com/badge.svg)](https://fuzz-opensource.herokuapp.com/)
==================

Magic Box modularizes Fuzz's magical implementation of Laravel's Eloquent models as injectable, masked resource repositories.

##### Magic Box has two goals:
1. To create a two-way interchange format, so that the JSON representations of models broadcast by APIs can be re-applied back to their originating models for updating existing resources and creating new resources.
2. Provide an interface for API clients to request exactly the data they want in the way they want.

Play with the [demo](http://explorer.magic-box.fuzzproductions.com/)!

## Installation/Setup
1. `composer require fuzz/magic-box`
1. Use or extend `Fuzz\MagicBox\Middleware\RepositoryMiddleware` into your project and register your class under the `$routeMiddleware` array in `app/Http/Kernel.php`. `RepositoryMiddleware` contains a variety of configuration options that can be overridden
1. If you're using `fuzz/api-server`, you can use magical routing by updating `app/Providers/RouteServiceProvider.php`, `RouteServiceProvider@map`, to include:

	```php
    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router $router
     * @return void
     */
    public function map(Router $router)
    {
        // Register a handy macro for registering resource routes
        $router->macro('restful', function ($model_name, $resource_controller = 'ResourceController') use ($router) {
            $alias = Str::lower(Str::snake(Str::plural(class_basename($model_name)), '-'));

            $router->resource($alias, $resource_controller, [
                'only' => [
                    'index',
                    'store',
                    'show',
                    'update',
                    'destroy',
                ],
            ]);
        });

        $router->group(['namespace' => $this->namespace], function ($router) {
            require app_path('Http/routes.php');
        });
    }
	```
1. Set up your MagicBox resource routes under the middleware key you assign to your chosen `RepositoryMiddleware` class
1. Set up a `YourAppNamespace\Http\Controllers\ResourceController`, [here is what a ResourceController might look like](https://gist.github.com/SimantovYousoufov/dea19adb1dfd8f05c1fcad9db976c247) .
1. Set up models according to `Model Setup` section

## Testing
Just run `phpunit` after you `composer install`.

## Eloquent Repository
`Fuzz\MagicBox\EloquentRepository` implements a CRUD repository that cascades through relationships,
whether or not related models have been created yet.

Consider a simple model where a User has many Posts. EloquentRepository's basic usage is as follows:

Create a User with the username Steve who has a single Post with the title Stuff.

```php
$repository = (new EloquentRepository)
    ->setModelClass('User')
    ->setInput([
        'username' => 'steve',
        'nonsense' => 'tomfoolery',
        'posts'    => [
            'title' => 'Stuff',
        ],
    ]);

$user = $repository->save();
```

When `$repository->save()` is invoked, a User will be created with the username "Steve", and a Post will
be created with the `user_id` belonging to that User. The nonsensical "nonsense" property is simply
ignored, because it does not actually exist on the table storing Users.

By itself, EloquentRepository is a blunt weapon with no access controls that should be avoided in any
public APIs. It will clobber every relationship it touches without prejudice. For example, the following
is a BAD way to add a new Post for the user we just created.

```php
$repository
    ->setInput([
        'id' => $user->id,
        'posts'    => [
            ['title' => 'More Stuff'],
        ],
    ])
    ->save();
```

This will delete poor Steve's first post—not the intended effect. The safe(r) way to append a Post
would be either of the following:

```php
$repository
    ->setInput([
        'id' => $user->id,
        'posts'    => [
            ['id' => $user->posts->first()->id],
            ['title' => 'More Stuff'],
        ],
    ])
    ->save();
```

```php
$post = $repository
    ->setModelClass('Post')
    ->setInput([
        'title' => 'More Stuff',
        'user' => [
            'id' => $user->id,
        ],
    ])
    ->save();
```

Generally speaking, the latter is preferred and is less likely to explode in your face.

The public API methods that return models from a repository are:

1. `create`
1. `read`
1. `update`
1. `delete`
1. `save`, which will either call `create` or `update` depending on the state of its input
1. `find`, which will find a model by ID
1. `findOrFail`, which will find a model by ID or throw `\Illuminate\Database\Eloquent\ModelNotFoundException`

The public API methods that return an `\Illuminate\Database\Eloquent\Collection` are:

1. `all`

## Filtering
`Fuzz\MagicBox\Filter` handles Eloquent Query Builder modifications based on filter values passed through the `filters` 
parameter.

Tokens and usage:  

|    Token   |           Description           |                     Example                    |
|:----------:|:-------------------------------:|:----------------------------------------------:|
| `^`        | Field starts with               | `https://api.yourdomain.com/1.0/users?filters[name]=^John`                    |
| `$`        | Field ends with                 | `https://api.yourdomain.com/1.0/users?filters[name]=$Smith`                   |
| `~`        | Field contains                  | `https://api.yourdomain.com/1.0/users?filters[favorite_cheese]=~cheddar`      |
| `<`        | Field is less than              | `https://api.yourdomain.com/1.0/users?filters[lifetime_value]=<50`            |
| `>`        | Field is greater than           | `https://api.yourdomain.com/1.0/users?filters[lifetime_value]=>50`            |
| `>=`       | Field is greater than or equals | `https://api.yourdomain.com/1.0/users?filters[lifetime_value]=>=50`           |
| `<=`       | Field is less than or equals    | `https://api.yourdomain.com/1.0/users?filters[lifetime_value]=<=50`           |
| `=`        | Field is equal to               | `https://api.yourdomain.com/1.0/users?filters[username]==Specific%20Username` |
| `!=`       | Field is not equal to           | `https://api.yourdomain.com/1.0/users?filters[username]=!=common%20username`  |
| `[...]`    | Field is one or more of         | `https://api.yourdomain.com/1.0/users?filters[id]=[1,5,10]`                   |
| `![...]`   | Field is not one of             | `https://api.yourdomain.com/1.0/users?filters[id]=![1,5,10]`                  |
| `NULL`     | Field is null                   | `https://api.yourdomain.com/1.0/users?filters[address]=NULL`                  |
| `NOT_NULL` | Field is not null               | `https://api.yourdomain.com/1.0/users?filters[email]=NOT_NULL`                |

### Filtering relations
Assuming we have users and their related tables resembling the following structure:

```php
[
    'username'         => 'Bobby',
    'profile' => [
        'hobbies' => [
            ['name' => 'Hockey'],
            ['name' => 'Programming'],
            ['name' => 'Cooking']
        ]
    ]
]
```

We can filter by users' hobbies with `users?filters[profile.hobbies.name]=^Cook`. Relationships can be of arbitrary 
depth.

### Filter conjuctions
We can use `AND` and `OR` statements to build filters such as `users?filters[username]==Bobby&filters[or][username]==Johnny&filters[and][profile.favorite_cheese]==Gouda`. The PHP array that's built from this filter is:

```php
[
    'username' => '=Bobby',
    'or'       => [
          'username' => '=Johnny',
          'and'      => [
              'profile.favorite_cheese' => '=Gouda',
          ]	
    ]
]
```

and this filter can be read as `select (users with username Bobby) OR (users with username Johnny who's profile.favorite_cheese attribute is Gouda)`.

## Model Setup
Models need to implement `Fuzz\MagicBox\Contracts\MagicBoxResource` before MagicBox will allow them to be exposed as a MagicBox resource. This is done so exposure is an explicit process and no more is exposed than is needed.

Models also need to define their own `$fillable` array including attributes and relations that can be filled through this model. For example, if a User has many posts and has many comments but an API consumer should only be able to update comments through a user, the `$fillable` array would look like:

```
protected $fillable = ['username', 'password', 'name', 'comments'];
```

MagicBox will only modify attributes/relations that are explicitly defined.

## Resolving models
Magic Box is great and all, but we don't want to resolve model classes ourselves before we can instantiate a repository...

If you've configured a RESTful URI structure with pluralized resources (i.e. `https://api.mydowmain.com/1.0/users` maps to the User model), you can use `Fuzz\MagicBox\Utility\Modeler` to resolve a model class name from a route name.

## Testing
`phpunit` :)

### TODO
1. Route service provider should be pre-setup
1. Support more relationships (esp. polymorphic relations) through cascading saves.
1. Support paginating nested relations
