Laravel Magic Box
==================

Magic Box modularizes Fuzz's magical implementation of Laravel's Eloquent models as injectable,
masked resource respositories. The goal of Magic Box is to create a two-way interchange format, so that
the JSON representations of models broadcast by APIs can be re-applied back to their originating models
for updating existing resources and creating new resources.

### Installation
1. Register the custom Fuzz Composer repository: ```composer config repositories.fuzz composer https://satis.fuzzhq.com``` 
1. Register the composer package: ```composer require fuzz/magic-box```

### Testing
Just run `phpunit` after you `composer install`.

### Eloquent Repository
`Fuzz\MagicBox\EloquentRepository` implements a CRUD repository that cascades through relationships,
whether or not related models have been created yet.

Consider a simple model where a User has many Posts. EloquentRepository's basic usage is as follows:

Create a User with the username Steve who has a single Post with the title Stuff.
```
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

```
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

```
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

```
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

### MagicMiddleware

@TODO document...

### Grouping

You can group results by a field. Example:

```
        GET /comments?group=user_id

```

@NOTE: as of right now it does not go through relations, it can only group on the top level resource.


### Aggregate Functions

The Repository can also perform a variety of aggregate methods, such as `count`, `max`, `min`, `avg`, and `sum`. Only one may be applied at a time, and is done so through the `aggregate` parameter.

Usage:
        
```
        ?aggregate[sum]=points
        ?aggregate[avg]=points
        ?aggregate[count]=id
        ?aggregate[max]=points
        ?aggregate[min]=points
```

All these endpoints also work well with grouping.



### Filtering
`Fuzz\MagicBox\Filter` handles Eloquent Query Builder modifications based on filter values passed through the `filters` 
parameter.

Tokens and usage:  

|    Token   |           Description           |                     Example                    |
|:----------:|:-------------------------------:|:----------------------------------------------:|
| `^`        | Field starts with               | `users?filters[name]=^John`                    |
| `$`        | Field ends with                 | `users?filters[name]=$Smith`                   |
| `~`        | Field contains                  | `users?filters[favorite_cheese]=~cheddar`      |
| `<`        | Field is less than              | `users?filters[lifetime_value]=<50`            |
| `>`        | Field is greater than           | `users?filters[lifetime_value]=>50`            |
| `>=`       | Field is greater than or equals | `users?filters[lifetime_value]=>=50`           |
| `<=`       | Field is less than or equals    | `users?filters[lifetime_value]=<=50`           |
| `=`        | Field is equal to               | `users?filters[username]==Specific%20Username` |
| `!=`       | Field is not equal to           | `users?filters[username]=!=common%20username`  |
| `[...]`    | Field is one or more of         | `users?filters[id]=[1,5,10]`                   |
| `![...]`   | Field is not one of             | `users?filters[id]=![1,5,10]`                  |
| `NULL`     | Field is null                   | `users?filters[address]=NULL`                  |
| `NOT_NULL` | Field is not null               | `users?filters[email]=NOT_NULL`                |

#### Filtering relations
Assuming we have users and their related tables resembling the following structure:

```
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

### TODO
1. Support more relationships (esp. polymorphic relations) through cascading saves.
1. Support sorting nested relations
1. Support paginating nested relations
