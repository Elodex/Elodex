# Elodex - **Elo**quent In**dex**ing Library

[![Latest Stable Version](https://poser.pugx.org/elodex/elodex/v/stable)](https://packagist.org/packages/elodex/elodex)
[![Build Status Master-Branch](https://travis-ci.org/Elodex/Elodex.svg?branch=master)](https://travis-ci.org/Elodex/Elodex)
[![StyleCI](https://styleci.io/repos/56262906/shield)](https://styleci.io/repos/56262906)
[![Total Downloads](https://poser.pugx.org/elodex/elodex/downloads)](https://packagist.org/packages/elodex/elodex)
[![License](https://poser.pugx.org/elodex/elodex/license)](https://packagist.org/packages/elodex/elodex)

Development branch: [![Build Status Develop-Branch](https://travis-ci.org/Elodex/Elodex.svg?branch=develop)](https://travis-ci.org/Elodex/Elodex)

_Elodex_ provides an easy way to implement synchronization of your [Laravel Eloquent][Laravel Eloquent] models with an [Elasticsearch][Elasticsearch] index.

Your Eloquent database will remain your main data source while you can use the full capacity of Elasticsearch for any index based search on your models.


## Table of Contents
- [Requirements](#requirements)
- [Branching Model](#branching-model)
- [Installation](#installation)
- [Laravel Integration](#laravel-integration)
  * [Configuration](#configuration)
  * [Add Indexing Capability to your Eloquent Model Classes](#add-indexing-capability-to-your-eloquent-model-classes)
- [Index Repositories](#index-repositories)
- [Documentation](#documentation)
- [License](#license)


## Requirements

Elodex requires Elasticsearch 2.0 or higher, PHP v5.6+ and Laravel 5.1+. Note that Laravel versions beyond 5.2 are currently not supported even though they might work.

Besides the technical requirements you should have a profound knowledge of Eloquent and you should be familiar with the basic [Elasticsearch terms][Elasticsearch terms] and how Elasticsearch works in general.


## Branching Model

This project uses the [Gitflow branching model][gitflow]:
- the **master** branch contains the latest **stable** version
- the **develop** branch contains the latest **unstable** development version
- all stable versions are tagged using semantic versioning


## Installation

Elodex can be directly added to your project via Composer:
```bash
$ composer require "elodex/elodex=^0.9"
```
Or you can manually add the required entry to your composer.json file in the `require` section :
```json
"require": {
  "elodex/elodex": "^0.9"
}
```


## Laravel Integration

To integrate Elodex into your Laravel application you first need to add the `IndexServiceProvider` to the list of service providers in the application configuration.

This can be done by editing the `app.php` file in the `config` folder. Search for the `providers` section and add a new entry:
```php
  /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
  */
  'providers' => [
    ...
    \Elodex\IndexServiceProvider::class,
  ],
```


### Configuration
Even though Elodex does ship with a default configuration which should work for standard Elasticsearch installations you usually want to specify your own settings.
You can do so by publishing the standard config file to your application:
```bash
$ php artisan vendor:publish --provider="Elodex\IndexServiceProvider"
```
This will copy a standard config to ``config/elodex.php``. Make sure your Elasticsearch host configuration is correct and that you specify a default index name for your application which will be used for all your indexed Eloquent models by default.
```php
  /*
    |--------------------------------------------------------------------------
    | Default Index Name
    |--------------------------------------------------------------------------
    |
  */
  'default_index' => 'my_app_index',
```


### Add Indexing Capability to your Eloquent Model Classes
There are two possibilities to add indexing capability to your Eloquent model classes.


#### 1. Using the IndexedModel Trait
To add the basic indexing functionality to your existing Eloquent models you can include the `IndexedModel` trait which automatically implements the needed `Contracts\IndexedModel` interface for you.
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Elodex\Contracts\IndexedModel as IndexedModelContract;
use Elodex\IndexedModel as IndexedModelTrait;

class Model extends BaseModel implements IndexedModelContract
{
    use IndexedModelTrait;
```

Note that the trait implements the `newCollection` method. You must make sure your Eloquent class doesn't overwrite this method, otherwise you're going to lose the convenient way to use indexing operations on the collections returned by model queries.

The `IndexedModel` trait does three things for you:

1. it implements the `Contracts\IndexedModel` interface and thus makes the model capable of being added to an index repository.
2. it adds a convenient way to access the default index manager and the default index repository for your model class.
3. it adds the methods to interact with the index repository. This includes adding your model instances to the index repository, removing them and performing an index based search.


#### 2. Deriving from the Elodex Model Class
Deriving from the abstract Elodex `Model` class is a better approach than the trait if your existing model directly inherits from the Eloquent base model class.
It gives you the possibility to override and thus extend the existing methods added by the `IndexedModel` trait without having to rewrite them completely.

A common use case would be if you want to change the document creation of your model.


## Index Repositories

All indexed model documents are managed in a repository with the type `IndexRepository`. Each model class has its own default index repository using its own type in the index.

This means you can't share an index repository with different model classes, trying to do so will result in exceptions during runtime.

The default index repository used for a class can be accessed through the `getClassIndexRepository` static method.

```php
$repository = User::getClassIndexRepository();
```

There's usually no need to access the index repository directly since the indexed model classes provide a more convenient method to manage the repository entries.


## Documentation
A detailed Elodex documentation can be [found here][Elodex Documentation].


## License

Elodex is an open source project licensed under the the [MIT license](http://opensource.org/licenses/MIT).
Please see [License File](LICENSE.txt) for further information.


[gitflow]: http://nvie.com/posts/a-successful-git-branching-model/ "Gitflow Branching model"
[Laravel Eloquent]: https://laravel.com/docs/5.2/eloquent "Laravel Eloquent"
[Elasticsearch]: https://www.elastic.co/guide/ "Elasticsearch Docs"
[Elasticsearch terms]: https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html "Elasticsearch Glossary of terms"
[Elodex]: https://github.com/elodex/Elodex "Elodex"
[Elodex Documentation]: https://github.com/Elodex/Documentation/blob/develop/00_TOC.md "Elodex Documentation"
