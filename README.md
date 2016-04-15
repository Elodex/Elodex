# Elodex - **Elo**quent In**dex**ing Library
-----

_Elodex_ provides an easy way to implement synchronization of your [Laravel Eloquent][Laravel Eloquent] models with an [Elasticsearch][Elasticsearch] index.

Your Eloquent database will remain your main data source while you can use the full capacity of Elasticsearch for any index based search on your models.

## Requirements
-----
Elodex requires Elasticsearch 2.0 or higher, PHP v5.5.9+ and Laravel 5.1+. Note that Laravel versions beyond 5.2 are currently not supported even though they might work.

Besides the technical requirements you should have a profound knowledge of Eloquent and you should be familiar with the basic [Elasticsearch terms][Elasticsearch terms] and how Elasticsearch works in general.


## Installation
-----
Elodex can be directly added to your project via Composer:
```bash
composer require "elodex/elodex=^0.9"
```
Or you can manually add the required entry to your composer.json file in the `require` section :
```json
"require": {
  "elodex/elodex": "^0.9"
}
```


## Laravel Integration
-----
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
php artisan vendor:publish --provider="Elodex\IndexServiceProvider"
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
```php
<?php

namespace App;

use Elodex\Model as ElodexModel;

class Model extends ElodexModel
{
  public function toIndexDocument()
  {
    $doc = parent::toIndexDocument();

    $doc['added_index_field'] = 'foo';

    return $doc;
  }

```


## Index Management
-----
General index management is available through the `IndexManager` class. You can either use dependency injection or access the `elodex.index` application singleton.
```php
app('elodex.index')->createIndex();
```

Another method is to call the `getDefaultIndexManager` method on your indexed model class
```php
User::getDefaultIndexManager()->createIndex();
```

The index manager is responsible for all administrative tasks related to your index. This includes creation and deletion of an index, putting index settings and mappings and using the [analyze][Elasticsearch indices - analyze] and [upgrade][Elasticsearch indices - upgrade] methods.

Index manager operations with an optional index name will use the the default index name specified in your configuration.


### Creating Indices
Before you can start synchronizing your indexed model classes with Elasticsearch you first need to create an index.
You usually create your index on your server while your app is in maintenance mode or during deployments.
Elodex includes a basic Artisan command for that.
```bash
php artisan es:create-index
```
Note that the command will fail if the index you specify already exists. To force the creation even if the index already exists use the `--force` parameter.
You can use the `--help` parameter to get a full list of all available parameters.

Even though the standard command is a good starting point you usually want to create an application specific command to support further settings and steps during index creation.
You can do so by deriving from `Console\CreateIndex` and overriding the `createIndex` method.
```php
<?php

namespace App\Console\Commands;

use Elodex\Console\CreateIndex as BaseCreateIndex;

class CreateIndex extends BaseCreateIndex
{
    ...

    protected function createIndex($indexName, $settings)
    {
        $settings = array_merge($settings, [
            // put our settings here
        ]);
        $mappings = [
            // put your mappings here
        ];

        $this->indexManager->createIndex($indexName, $settings, $mappings);
    }
```
The parent class automatically makes an index manager instance available which can be used for all index management operations.
Consult the [Laravel documentation][Laravel Artisan] on how to make the Artisan command available to your application.

Index mappings should usually be set during index creation to prevent having to [reindex your data][Elasticsearch reindexing your data].
A custom Artisan command as described above in the `CreateIndex` class is a good place to do so.

The structure of the mappings array is defined by [Elasticsearch][Elasticsearch create indices - mappings]:
```php
$mappings = [
    'type_1' => [
        'properties' => [ ... ],
    ],
    'type_2' => [
        'properties' => [ ... ],
    ],
],
```
You can use the `getIndexTypeName` method of your models to get the type name.
For an in-depth description about index creations in Elasticsearch take a look into the [Elasticsearch documentation][Elasticsearch create indices].

The _Property Mappings_ section describes how to automatically create mappings for your model classes which can then be used in the mappings array.

If you decide not to use the command line to create your indices you can make use of the `IndexManager` class as described in the _Index Management_ section.


### Deleting Indices
Elodex provides an Artisan command to delete any existing index.
```bash
php artisan es:delete-index
```

Inside your application the `deleteIndex` method of the `IndexManager` class will basically do the same.

```php
app('elodex.index')->deleteIndex('my_index');
```


### Property Mappings
One of the most important things for your index to work as expected is to create the desired property mappings for your Eloquent attributes.
That's where you tell your index how your indexed documents should be analyzed. E.g. what type your attributes have, which analyzer should be used and whether your relationships should be treated as nested objects.

Elodex provides a method to create default mappings based on the attribute settings you've (hopefully) already defined on your model class.
The following class properties will be used to create the mapping in the specified order:
1. **`dates`**: all attributes specified here will be mapped to an Elasticsearch date format accordingly. The `indexMappingDateFormat` property defines which date formatting will be used for the mapping, the default format is `yyyy-MM-dd HH:mm:ss`.  
*This does not work if your Eloquent model class uses a custom date through the `dateFormat` property !*
2. **`casts`**: the types specified in this property will be mapped to [Elasticsearch field dataypes][Elasticsearch field datatypes]. The attribute will not be added to the mapping if the type cannot be mapped.
3. **`indexMappingProperties`**: this property can be used if you want to specify any additional custom mappings.

Custom property mappings defined by the `indexMappingProperties` array overwrite any automatically determined mappings and have the following structure:
```php
class User extends Model implements IndexedModelContract
{
    use IndexedModelTrait;

    protected $indexMappingProperties = [
        'first_name' => [            // The name of the attribute
            'type' => 'string',      // Elasticsearch field datatype (NOT the PHP data type!)
            'analyzer' => 'simple',  // Elasticsearch analyzer to use for this field
        ],
    ];
```
For a detailed description of the needed structure for custom index mappings visit the [Elasticsearch documentation][Elasticsearch mapping] site.

Note that the mapping will respect the visibility setting of attributes specified by the `visible` and `hidden` properties on your class.
I.e. hidden properties will be excluded from the generated mapping.

To get all mappings for your model class in order to send them to the index manager you can call the `getIndexMappingProperties` method on a model instance.
```php
$mappings = (new Model)->getIndexMappingProperties();
```

The result can be used during the index creation as described in the _Create indices_ section.
```php
$mappings = [];

$foo = new Foo;
$mappings[$foo->getIndexTypeName()] = [
  '_source' => ['enabled' => true],
  'properties' => $foo->getIndexMappingProperties()
];

$indexManager->createIndex($indexName, $settings, $mappings);
```

If you want to update a class mapping after an index has been created or if you don't want to define the mappings during index creation you can do so by using the `putIndexMappings` class method.
```php
Model::putIndexMappings();
```
Alternatively you could use the `IndexManager` class and its `putMappings` method
```php
$defaultIndex = $indexManager->getDefaultIndex();
$mappings = $model->getIndexMappingProperties();

$indexManager->putMappings($defaultIndex, $model->getIndexTypeName(), $mappings);
```
The general rules about changing the index mappings apply. I.e. you might need to close your index or even reindex your data to change the mappings.

Note that Elasticsearch actually doesn't require you to explicitly put property mappings in order to add documents because it supports a [dynamic mapping][Elasticsearch dynamic mapping] mechanism.
But there's hardly any use case in which you would not want to use an explicit mapping for your Eloquent model classes.

#### Property Mappings for Relationships
All property mappings for the relationships of a model are defined and created by the parent.
Using the `IndexedModel` trait on a related model class is not needed and will have no effect on the mappings created by the parent for this relationship.
I.e. a parent never calls `getIndexMappingProperties` on related models even if they implement this method.

You can however define a `indexMappingProperties` on your related model class which (if existent) will be used by the parent.
Even though this is not really necessary since you could as well directly define any custom property mappings of your relationships in the `indexMappingProperties` of your parent.


### Indexing Model Relationships
Your Eloquent models are usually not flat and may include several different relationships which you might want to include in your indexed document as nested objects.
You should be careful though and be aware of the implications that come with nested objects as described [here][Elasticsearch modeling your data].

To automatically add a relationship to the indexed document of a model you can simply add it to the `indexRelations` property.
```php
class User extends Model implements IndexedModelContract
{
    use IndexedModelTrait;

    protected $indexRelations = [ 'comments' ];
```
Indexed relations will be automatically loaded before the document representation for a model is created.
Any other already loaded relation will be ignored and not be included into the document!

Note that the Eloquent visibility properties `visible` and `hidden` don't have any effect on the relations defined in the `indexRelations` property.
A specifically hidden index relation will still load during the index document creation and will be integrated into the parents document.


### Document Creation for the Index
The standard implementation of the `toIndexDocument` method provided by Elodex to create a document for a model works very similar to the `toArray` method.
With the exception of the way relationships are integrated into the parent document. See the next section for more details about documents for relationships.  
If you want to modify the way indexed documents are being created you can do so by implementing your own `toIndexDocument` in your model class.

Partial updates use the `getChangedIndexDocument` to create a document for the changed data.

#### Documents of related Models
As described in *Indexing Model Relationships* any relationship specified in `indexRelations` will cause the relationship to be added to your parent's document.
Related models implementing the `Contracts\IndexedDocument` interface will be added with their document representation to the parent document.
If a related model doesn't implement the interface a fallback to the standard serialization method with `toArray` will be used.


## Index Repositories
-----
All indexed model documents are managed in a repository with the type `IndexRepository`. Each model class has its own default index repository using its own type in the index.

This means you can't share an index repository with different model classes, trying to do so will result in exceptions during runtime.

The default index repository used for a class can be accessed through the `getClassIndexRepository` static method.

```php
$repository = User::getClassIndexRepository();
```

There's usually no need to access the index repository directly since the indexed model classes provide a more convenient method to manage the repository entries.


### Adding Models to the Index
To add the document of a single model to your default index repository you can call `addToIndex` on the model instance.
```php
$user = new User();
$user->save();
$user->addToIndex();
```
Note that `addToIndex` will fail and throw an exception if the document already exists.

As described in _Document Creation for the Index_ the index document representation of a model and all its related objects is created by the `toIndexDocument` method.

If you want to add or replace a document you can use the `saveToIndex` method, this method will not fail if a document doesn't exist already.
```php
$user->saveToIndex();
```

You may also add a collection result of a model query to the index using the same methods.
```php
User::all()->addToIndex();

User::all()->saveToIndex();
```

Index operations on collections are always [bulk operations][Elasticsearch bulk API].
A `BulkOperationException` exception will be thrown if the operation fails for any of the specified models in the collection.  
Bulk deletions however don't follow this rule since Elasticsearch doesn't set the error flag in this case.

**Careful:** Your index will be in an inconsistent state after a bulk operation fails partially, Elasticsearch doesn't support transactions!
The bulk operation exception instance will contain a list of failed items which can be accessed through the `getFailedItems` method.
It's the programmers responsibility to catch and handle the `BulkOperationException` and to bring the index back to a consistent state.


### Removing Models from the Index
To remove a model's document from its index repository just call `removeFromIndex` on the instance.

```php
$user->removeFromIndex();
```

If you want to remove multiple documents as a result of a query you can do so by calling the same method on the resulting collection
```php
User::all()->removeFromIndex();
```

### Partial Updates
Partially updating your model is supported through the `updateIndex` method.

```php
$user->updateIndex();
```

It uses the `getChangedIndexDocument` on your model to create a document of changed attributes which will be used for the update operation.
The standard implementation of this method temporarily hides all unchanged attributes and then serializes attributes just like the `toArray` method does.

`getChangedIndexDocument` does not include any relationships or changed data from relationships.
This is because Eloquent doesn't have a tight binding between parents and their related models besides the ability to touch the timestamp of parents.

In other words: ***Partial updates are not supported for models with indexed relationships!***

If you partially update a model with indexed relationships it might become inconsistent because their related models didn't get updated in the nested documents.

Generally speaking: there're very few reasons to use partial updates at all due to the fact [how partial updates work in Elasticsearch][Elasticsearch partial updates].
Elasticsearch will always internally perform a full document update anyways.



## Index Search
-----
Indexed model classes provide a simple method to search for indexed documents of their type without having to directly access the corresponding index repository.
```php
$results = User::indexSearch()->get();
```

All search operations use a `Search` query builder utilizing the [Elasticsearch DSL library][Elasticsearch DSL library].
You can create a new search query builder with the static `indexSearch` method or with the `newIndexSearch` method on the model instance.  
The Elodex `Search` class provides some convenient methods to add common search queries and parameters just like the Eloquent query builder does.

```php
$results = User::indexSearch()
  ->prefix('first_name', 'John')
  ->sort('first_name')
  ->get();
```
Multiple queries are combined with a `must` [bool query][Elasticsearch bool query] by default.

You can specify other occurrence types as well:
```php
$results = User::indexSearch()
  ->prefix('first_name', 'John', BoolQuery::SHOULD)    // or
  ->match('last_name', 'Miller', BoolQuery::MUST_NOT)  // and not
  ->wildcard('street', 'Main*', BoolQuery::SHOULD)     // or
  ->sort('first_name')
  ->get();
```

To add more complex queries use the `addQuery` method.
Consult the [Elasticsearch DSL documentation][Elasticsearch DSL] on how to build search queries of any type.

```php
$search = User::indexSearch();

$companyMatch = new MatchQuery('company.name', $term, ['type'=>'phrase_prefix']);
$nestedCompanyMatch = new NestedQuery('company', $companyMatch);

$search->addQuery(nestedCompanyMatch);

$results = $search->get();
```


### Search Results
Search operations return a `SearchResult` object containing information about the search and the hits returned by the index.
Note that the documents returned by the `getDocuments` method are not actual Eloquent model objects. It returns an array of documents keyed by the identifier of the document. The desired order of the search result as specified by any search ordering remains intact.  
This gives you the opportunity to work with the documents returned by the index instead of Eloquent model instances and has the advantage of not having to perform an extra database query if not absolutely necessary.

If you need the models from your DB associated with the documents you may use the `getItems` method on the search result instance.
This implies a certain overhead to fetch and create the model instances.
```php
$models = $results->getItems();
```
The search results object furthermore contains additional information about the result of the search like the time the search took and info about the scoring.

You should be aware that searches might fail partially due to timeouts. You can check this case with the `timedOut` method.
Timeouts are usually not critical, temporary and only cause some of the search results to not be returned.
The `getShards` method returns detailed information about how many shards failed.


### Searching Relationships
As explained in the _Indexing Model Relationships_ section relationships are by default mapped as nested objects inside indexed documents.

This means that you have to use [nested queries][Elasticsearch DSL nested query] if you want to search in fields of your nested documents.

```php
// Add a query for the 'comment' relationship of the user.
$commentQuery = new WildcardQuery('comment.description', "*hello*");
$nestedQuery = new NestedQuery('comment', $commentQuery);

$search = User::indexSearch()->addQuery($nestedQuery);

$results = $search->get();
```


### Pagination
You can paginate search results by calling the `paginate` method on `Search` query builder instances.
Pagination in Elodex basically works like [pagination for Eloquent queries][Laravel Pagination].
```php
$results = User::indexSearch()
  ->paginate(5);
```
The paginator will return the documents of the search result by default if you iterate through the elements.  
If you need the model instances to be loaded from the DB you can call the `getItems` method just like you can on the `SearchResult` instance itself.
```php
$users = $results->getItems();
```
Accessing the underlying search result instance is possible through the `getSearchResult` method.

You should be aware of the [pagination limitations][Elasticsearch pagination] that come with Elasticsearch.


## Index Synchronization
-----
Once you've filled your index repositories with your existing Eloquent models you usually want to keep your index in sync with your database.

This can be easily achieved by adding an [event subscriber][Laravel Event Subscribers] for all relevant Eloquent events.

```php
class IndexSyncHandler implements ShouldQueue
{
    public function onCreated($user)
    {
        $user->saveToIndex();
    }

    public function onSaved($user)
    {
        $user->saveToIndex();
    }

    public function onDeleted($user)
    {
        $user->removeFromIndex();
    }

    public function onRestored($user)
    {
        $user->saveToIndex();
    }

    public function subscribe($events)
    {
        $events->listen(
            'eloquent.created: '.User::class, static::class.'@onCreated'
        );

        $events->listen(
            'eloquent.saved: '.User::class, static::class.'@onSaved'
        );

        $events->listen(
            'eloquent.deleted: '.User::class, static::class.'@onDeleted'
        );

        $events->listen(
            'eloquent.restored: '.User::class, static::class.'@onRestored'
        );
    }
}
```

You might have noticed that `ShouldQueue` is used which will cause all event methods to be called on a queue, most likely asynchronously unless you use the `Sync` queue.
Using queued methods is highly recommended!

Index operations may fail or throw exceptions and you don't want that to happen as part of a user's request cycle.
Queues will automatically retry failed jobs, give you the opportunity to retry failed jobs and handle errors outside of the request cycle.


### Synchronizing Index Relationships
If your indexed document contains nested objects you need to make sure that all parents of a changed related model get updated as well.

It's pretty obvious if you look at the structure of nested documents. Let's assume you've got an array of comments and in this case a comment is the parent of a user:
```php
  [
    'text' => 'Foo',
    'user' => [
      'id' => 1,
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]
  ],
  [
    'text' => 'Bar',
    'user' => [
      'id' => 1,
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]
  ],
```

If the user _John Doe_ changes, all comment entries containing this user have to be updated as well.
```php
  public function onSaved($user)
  {
    $user->saveToIndex();

    $user->comments->saveToIndex();
  }
```

So let's say you've got 1000 comments which belong to 1 user, that would mean 1001 documents have to be updated if this user changes.
That's something you should keep in mind if you decide to use relationships in indices.



[Laravel Eloquent]: https://laravel.com/docs/5.2/eloquent "Laravel Eloquent"
[Laravel Artisan]: https://laravel.com/docs/5.2/artisan "Laravel Artisan"
[Laravel Event Subscribers]: https://laravel.com/docs/5.2/events#event-subscribers "Laravel Event Subscribers"
[Laravel Pagination]: https://laravel.com/docs/5.2/pagination#displaying-results-in-a-view "Laravel Pagination"
[Elasticsearch]: https://www.elastic.co/guide/ "Elasticsearch Docs"
[Elasticsearch terms]: https://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html "Elasticsearch Glossary of terms"
[Elasticsearch create indices]: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html "Elasticsearch create indices"
[Elasticsearch create indices - mappings]: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html#mappings "Elasticsearch create indices - mappings"
[Elasticsearch indices - analyze]: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-analyze.html "Elasticsearch indices - analyze"
[Elasticsearch indices - upgrade]: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-upgrade.html "Elasticsearch indices - upgrade"
[Elasticsearch mapping]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html "Elasticsearch mapping"
[Elasticsearch field datatypes]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#_field_datatypes "Elasticsearch field datatypes"
[Elasticsearch dynamic mapping]: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#_dynamic_mapping "Elastic search dynamic mapping"
[Elasticsearch modeling your data]: https://www.elastic.co/guide/en/elasticsearch/guide/current/modeling-your-data.html "Elasticsearch modeling your data"
[Elasticsearch partial updates]: https://www.elastic.co/guide/en/elasticsearch/guide/current/partial-updates.html "Elasticsearch partial updates"
[Elasticsearch bulk API]: https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html "Elasticsearch bulk API"
[Elasticsearch reindexing your data]: https://www.elastic.co/guide/en/elasticsearch/guide/current/reindex.html "Elasticsearch reindexing your data"
[Elasticsearch pagination]: https://www.elastic.co/guide/en/elasticsearch/guide/current/pagination.html "Elasticsearch pagination"
[Elasticsearch bool query]: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html "Elasticsearch bool query"
[Elodex]:https://github.com/elodex/Elodex "Elodex"
[Elasticsearch DSL library]: https://github.com/ongr-io/ElasticsearchDSL "Elasticsearch DSL library"
[Elasticsearch DSL]: https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md "Elasticsearch DSL"
[Elasticsearch DSL nested query]: https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/Query/Nested.md "Elasticsearch DSL nested query"