[![Build Status](https://api.travis-ci.org/tomwalder/php-gds.svg)](https://travis-ci.org/tomwalder/php-gds)
[![Coverage Status](https://coveralls.io/repos/github/tomwalder/php-gds/badge.svg?branch=master)](https://coveralls.io/github/tomwalder/php-gds?branch=master)

# Google Cloud Datastore Library for PHP #

[Google Cloud Datastore](https://cloud.google.com/datastore/) is a great NoSQL solution (hosted, scalable, free up to a point), but it can be tricky (i.e. there's lots of code glue needed) to get even the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for you to get started with and to use Datastore in your applications.

## Quick Start ##
```bash
composer require "tomwalder/php-gds:^5.0"
```
```php
// Build a new entity
$obj_book = new GDS\Entity();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Write it to Datastore
$obj_store = new GDS\Store('Book');
$obj_store->upsert($obj_book);

// Fetch all books
foreach($obj_store->fetchAll() as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn} <br />", PHP_EOL;
}
```

## New in Version 5.0 ##

**As of version 5 (May 2021), this library provides support for**

* PHP 7 second-generation App Engine runtimes - using the REST API by default
* PHP 7 "anywhere" (e.g. Google Compute Engine, Cloud Run, GKE) - using REST or gRPC

**Key features removed from version 5 onwards**

* PHP 5 support
* Support for the legacy "Protocol Buffer" API built into first-generation App Engine runtimes

If you need to continue running applications on that infrastructure, stick to version 4.x or earlier.

## Table of Contents ##

* [Examples](#examples)
* [Getting Started](#getting-started) including installation with Composer and setup for GDS Emulator
* [Defining Your Model](#defining-your-model)
* [Creating Records](#creating-records)
* [Timezones and DateTime](#updated-timezone-support)
* [Geopoint Support](#geopoint)
* [Queries, GQL & The Default Query](#queries-gql--the-default-query)
* [Multi-tenant Applications & Data Namespaces](#multi-tenant-applications--data-namespaces)
* [Entity Groups, Hierarchy & Ancestors](#entity-groups-hierarchy--ancestors)
* [Transactions](#transactions)
* [Data Migrations](#data-migrations)
* [More About Google Cloud Datastore](#more-about-google-cloud-datastore)
* [Unit Tests](#unit-tests)
* [Footnotes](#footnotes)

## Using the REST API (default from 5.0) ##

As of PHP-GDS version 5, the REST API Gateway is the default.

It will attempt to auto-detect your Google Project ID - and usually the Google auth library will use the default application credentials.

You might need to set an environment variable with the path to your JSON credentials file first, usually if you're running outside of App Engine or Google Compute Engine.

```php
putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/my/credentials.json');

// A regular Store, but with a custom Gateway
$obj_book_store = new GDS\Store('Book', new \GDS\Gateway\RESTv1('my-project-id'));
```

You can find out more about the auth system here: [Google Auth Library for PHP](https://github.com/google/google-auth-library-php)

You can download a service account JSON file from the Google Cloud Console `API Manager > Credentials`.

## Firestore in Datastore Mode ##

If you are using PHP-GDS version 4 or earlier, and Firestore in Datastore mode from App Engine standard (first generation), you may run into `Internal Error`s from the `Protobuf` gateway.

You can resolve this by using the `RESTv1` Gateway & APIs. [See here for basic guidance](#using-the-datastore-rest-api-v1-sep-2016)

More details and recommended upgrade paths to come. Along with better gRPC support for outside App Engine.

## Examples ##

I find examples a great way to decide if I want to even try out a library, so here's a couple for you. 

```php
// Build a new entity
$obj_book = new GDS\Entity();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Write it to Datastore
$obj_store = new GDS\Store('Book');
$obj_store->upsert($obj_book);
```

You can also use the [Alternative Array Syntax](#alternative-array-syntax) for creating Entity objects, like this

```php
$obj_book = $obj_store->createEntity([
    'title' => 'The Merchant of Venice',
    'author' => 'William Shakespeare',
    'isbn' => '1840224312'
]);
```

Now let's fetch all the Books from the Datastore and display their titles and ISBN numbers

```php
$obj_store = new GDS\Store('Book');
foreach($obj_store->fetchAll() as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn} <br />", PHP_EOL;
}
```

### More about the Examples ###

These initial examples assume you are either running a Google AppEngine application or in a local AppEngine dev environment. 
In both of these cases, we can auto detect the **dataset**.

We use a `GDS\Store` to read and write `GDS\Entity` objects to and from Datastore. 

These examples use the generic `GDS\Entity` class with a dynamic Schema. See [Defining Your Model](#defining-your-model) below for more details on custom Schemas and indexed fields.

### Demo Application ###

A simple guest book application

Application: http://php-gds-demo.appspot.com/

Code: https://github.com/tomwalder/php-gds-demo

## Changes in Version 5 ##

* Add PHP 7 support
* Remove PHP 5 support
* Remove App Engine first-generation runtime support (inc direct Protocol Buffer API)

### Updated Timezone Support ###

In 5.1, timezone support has been improved for `DateTime` objects going in & out of Datastore.

#### How the data is stored
Datstore keeps the data recorded as UTC. When you browse data in the Google Cloud Console, they represent it in your locale.

#### Data coming out through PHP-GDS as Entities
You can now expect any `DateTime` object coming out of Datastore from PHP-GDS to have your current PHP default timezone applied. Example follows:

```php
date_default_timezone_set('America/New_York');

$obj_store = new GDS\Store('Book');
$obj_book = $obj_store->fetchOne();
echo $obj_book->published->format('c'); // 2004-02-12T15:19:21-05:00
echo $obj_book->published->getTimezone()->getName(); // America/New_York
```

#### Data going in - multi format support
If you pass in a `DateTime` object (or anything matching `DateTimeInterface`), we will respect the timezone set on it.

Any other string-based value passed in for a `datetime` field will be converted to a `DateTimeImmutable` object before being converted to UTC, using the standard PHP methods:
https://www.php.net/manual/en/datetime.construct.php

This means that unless using a timestamp value (e.g. `@946684800`), or a value with a timezone already stated (e.g. `2010-01-28T15:00:00+02:00`), we will assume the value is in your current timezone context.

## Changes in Version 4 ##

* More consistent use of `DateTime` objects - now all result sets will use them instead of `Y-m-d H:i:s` strings   
* Move the `google/auth` to an optional dependency - if you need the REST API

## Changes in Version 3 ##

* Support for the new **Datastore API, v1 - via REST**
* Removal of support for the old 1.x series "PHP Google API Client"
* **GeoPoint data is now supported over the REST API** v1 as well as ProtoBuf

## Getting Started ##

Are you sitting comfortably? Before we begin, you will need: 
- a Google Account (doh), usually for running AppEngine - but not always
- a Project to work on with the "Google Cloud Datastore API" turned ON [Google Developer Console](https://console.developers.google.com/)

If you want to use the JSON API from remote or non-App Engine environments, you will also need
- Application default credentials **OR**
- a "Service account" and the JSON service key file, downloadable from the Developer Console

### Composer, Dependencies ###

To install using Composer

```bash
composer require "tomwalder/php-gds:^5.0"
```

### Use with the Datastore Emulator ###
Local development is supported using the REST Gateway and the Datastore Emulator.

Detailed instructions can be found here:
https://cloud.google.com/datastore/docs/tools/datastore-emulator

## Defining Your Model ##

Because Datastore is schemaless, the library also supports fields/properties that are not explicitly defined. But it often makes a lot of sense to define your Entity Schema up front.

Here is how we might build the Schema for our examples, with a Datastore Entity Kind of "Book" and 3 fields.

```php
$obj_schema = (new GDS\Schema('Book'))
   ->addString('title')
   ->addString('author')
   ->addString('isbn');
   
// The Store accepts a Schema object or Kind name as its first parameter
$obj_book_store = new GDS\Store($obj_schema);
```

By default, all fields are indexed. An indexed field can be used in a WHERE clause. You can explicitly configure a field to be not indexed by passing in `FALSE` as the second parameter to `addString()`.

If you use a dynamic schema (i.e. do not define on, but just use the Entity name) then all fields will be indexed for that record.

Available Schema configuration methods:
- `GDS\Schema::addString`
- `GDS\Schema::addInteger`
- `GDS\Schema::addDatetime`
- `GDS\Schema::addFloat`
- `GDS\Schema::addBoolean`
- `GDS\Schema::addStringList`
- `GDS\Schema::addGeopoint`

Take a look at the `examples` folder for a fully operational set of code.

## Creating Records ##

### Alternative Array Syntax ###

There is an alternative to directly constructing a new `GDS\Entity` and setting its member data, which is to use the `GDS\Store::createEntity` factory method as follows.

```php
$obj_book = $obj_book_store->createEntity([
    'title' => 'The Merchant of Venice',
    'author' => 'William Shakespeare',
    'isbn' => '1840224312'
]);
```

## Special Properties ##

Other than scalar values, there are two "object" data types supported:

### DateTime ###

Support for DateTime object binding (also see query parameter binding below)

```php
$obj_book = $obj_book_store->createEntity([
    'title' => 'Some Book',
    'author' => 'A N Other Guy',
    'isbn' => '1840224313',
    'published' => new DateTime('-5 years')
]);
```

### Geopoint ###

The library has recently had support added for Geopoint properties.

```php
$obj_schema->addGeopoint('location');
```

Then when setting data, use the `Geopoint` object

```php
$obj_person->location = new GDS\Property\Geopoint(53.4723272, -2.2936314);
```

And when pulling geopoint data out of a result:

```php
echo $obj_person->location->getLatitude();
echo $obj_person->location->getLongitude();
```

**It is not currently possible to query Geopoint fields, although this feature is in Alpha with Google**

## Queries, GQL & The Default Query ##

The `GDS\Store` object uses Datastore GQL as its query language. Here is an example:

```php
$obj_book_store->fetchOne("SELECT * FROM Book WHERE isbn = '1853260304'");
```

And with support for named parameter binding (strings, integers) (*this is recommended*)

 ```php
$obj_book_store->fetchOne("SELECT * FROM Book WHERE isbn = @isbnNumber", [
    'isbnNumber' => '1853260304'
]);
```

Support for DateTime object binding

 ```php
$obj_book_store->fetchOne("SELECT * FROM Task WHERE date_date < @now", [
    'now' => new DateTime()
]);
```

We provide a couple of helper methods for some common (root Entity) queries, single and batch  (much more efficient than many individual fetch calls):

- `GDS\Store::fetchById`
- `GDS\Store::fetchByIds` - batch fetching
- `GDS\Store::fetchByName`
- `GDS\Store::fetchByNames` - batch fetching

When you instantiate a store object, like `BookStore` in our example, it comes pre-loaded with a default GQL query of the following form (this is "The Default Query")

```sql
SELECT * FROM <Kind> ORDER BY __key__ ASC
```

Which means you can quickly and easily get one or many records without needing to write any GQL, like this:

```php
$obj_store->fetchOne();     // Gets the first book
$obj_store->fetchAll();     // Gets all books
$obj_store->fetchPage(10);  // Gets the first 10 books
```

### 1000 Result Batch Limit ###

By default, this library will include a 1,000 record "batch size".

This means calling `fetchAll()` will only return 1,000 records.

I suggest paging your results if you need more than 1,000 records using `fetchPage()`.

### GQL on the Local Development Server ###

At the time of writing, the Google App Engine local development server does not support GQL. So, **as of 2.0 I have included a basic GQL parser, which is only used in local development environments** and should mean you can run most application scenarios locally as you can on live.

The GQL parser should be considered a "for fun" tool, rather than a production-ready service.

Feedback very much appreciated - if you have GQL queries that fail to run, just raise an issue and I'll see what I can do (or fork & PR!).

### Pagination ###

When working with larger data sets, it can be useful to page through results in smaller batches. Here's an example paging through all Books in 50's.

```php
$obj_book_store->query('SELECT * FROM Book');
while($arr_page = $obj_book_store->fetchPage(50)) {
    echo "Page contains ", count($arr_page), " records", PHP_EOL;
}
```

#### Limits, Offsets & Cursors ####

In a standard SQL environment, the above pagination would look something like this:

- `SELECT * FROM Book LIMIT 0, 50` for the first page 
- `SELECT * FROM Book LIMIT 50, 50` for the second, and so on.

Although you can use a very similar syntax with Datastore GQL, it can be unnecessarily costly. This is because each row scanned when running a query is charged for. So, doing the equivalent of `LIMIT 5000, 50` will count as 5,050 reads - not just the 50 we actually get back.

This is all fixed by using Cursors. The implementation is all encapsulated within the `GDS\Gateway` class so you don't need to worry about it.

Bototm line: the bult-in pagination uses Cursors whenever possible for fastest & cheapest results.

#### Tips for LIMIT-ed fetch operations ####

Do not supply a `LIMIT` clause when calling 
- `GDS\Store::fetchOne` - it's done for you (we add `LIMIT 1`)
- `GDS\Store::fetchPage` - again, it's done for you and it will cause a conflict. 

#### Pricing & Cursor References ####

- [Query Cursors](https://cloud.google.com/datastore/docs/concepts/queries#Datastore_Query_cursors)
- [Costs for Datastore Calls](https://cloud.google.com/appengine/pricing)
- [Datastore Quotas](https://cloud.google.com/appengine/docs/quotas#Datastore)

## Multi-tenant Applications & Data Namespaces ##

Google Datastore supports segregating data within a single "Dataset" using something called Namespaces.

Generally, this is intended for multi-tenant applications where each customer would have separate data, even within the same "Kind".

This library supports namespaces, and they are be configured per `Gateway` instance by passing in the optional 3rd namespace parameter.

<strong>ALL operations carried out through a Gateway with a namespace configured are done in the context of that namespace. The namespace is automatically applied to Keys when doing upsert/delete/fetch-by-key and to Requests when running GQL queries.</strong>

```php
// Create a store for a particular customer or 'application namespace'
$obj_gateway = new \GDS\Gateway\RESTv1('project-id', 'namespace');
$obj_store = new \GDS\Store('Book', $obj_gateway);
```

Further examples are included in the examples folder.

## Entity Groups, Hierarchy & Ancestors ##

Google Datastore allows for (and encourages) Entities to be organised in a hierarchy.

The hierarchy allows for some amount of "relational" data. e.g. a `ForumThread` entity might have one more more `ForumPosts` entities as children.

Entity groups are quite an advanced topic, but can positively affect your application in a number of areas including
 
- Transactional integrity
- Strongly consistent data

At the time of writing, I support working with entity groups through the following methods

- `GDS\Entity::setAncestry`
- `GDS\Entity::getAncestry`
- `GDS\Store::fetchEntityGroup`

## Transactions ##

The `GDS\Store` supports running updates and deletes in transactions.

To start a transaction

```php
$obj_store->beginTransaction();
```

Then, any operation that changes data will commit *and consume* the transaction. So an immediate call to another operation WILL NOT BE TRANSACTIONAL.

```php
// Data changed within a transaction
$obj_store->upsert($obj_entity);

// Not transactional
$obj_store->delete($obj_entity);
```

Watch out for `GDS\Exception\Contention` exceptions - they should be thrown by the library if you manage to hit Datastore contention locally in development or through the live Gateways.

## Custom Entity Classes and Stores ##

Whilst you can use the `GDS\Entity` and `GDS\Store` classes directly, as per the examples above, you may find it useful to extend one or the other.

For example

```php
class Book extends GDS\Entity { /* ... */ }
$obj_store->setEntityClass('\\Book');
```

This way, when you pull objects out of Datastore, they are objects of your defined Entity class.

The `Schema` holds the custom entity class name - this can be set directly, or via the `Store` object.

## Re-indexing ##

When you change a field from non-indexed to indexed you will need to "re-index" all your existing entities before they will be returned in queries run against that index by Datastore. This is due to the way Google update their BigTable indexes.

I've included a simple example (paginated) re-index script in the examples folder, `reindex.php`.

## Data Migrations ##

Using multiple Gateway classes, you can move data between namespaces 

```php
// Name-spaced Gateways
$obj_gateway_one = new \GDS\Gateway\RESTv1('project-id', 'namespace_one');
$obj_gateway_two = new \GDS\Gateway\RESTv1('project-id', 'namespace_two');

// Grab some books from one
$arr_books = (new \GDS\Store('Book', $obj_gateway_one))->fetchPage(20);

// And insert to two
(new \GDS\Store('Book', $obj_gateway_two))->upsert($arr_books);
```

and between local and live environments. 

```php
// Local and Remote Gateways
$obj_gateway_local = new \GDS\Gateway\ProtoBuf();
$obj_gateway_remote = new \GDS\Gateway\RESTv1('project-name');

// Grab some books from local
$arr_books = (new \GDS\Store('Book', $obj_gateway_local))->fetchPage(20);

// And insert to remote
(new \GDS\Store('Book', $obj_gateway_remote))->upsert($arr_books);
```

*Note: In both of these examples, the entities will be inserted with the same KeyID or KeyName*

## More About Google Cloud Datastore ##

What Google says:

> "Use a managed, NoSQL, schemaless database for storing non-relational data. Cloud Datastore automatically scales as you need it and supports transactions as well as robust, SQL-like queries."

https://cloud.google.com/datastore/

### Specific Topics ###

A few highlighted topics you might want to read up on
- [Entities, Data Types etc.](https://cloud.google.com/datastore/docs/concepts/entities)
- [More information on GQL](https://cloud.google.com/datastore/docs/concepts/gql)
- [GQL Reference](https://cloud.google.com/datastore/docs/apis/gql/gql_reference)
- [Indexes](https://cloud.google.com/datastore/docs/concepts/indexes)
- [Ancestors](https://cloud.google.com/datastore/docs/concepts/entities#Datastore_Ancestor_paths)
- [More about Datastore Transactions](https://cloud.google.com/datastore/docs/concepts/transactions)

## Unit Tests ##

A full suite of unit tests is in the works. Assuming you've installed `php-gds` and its dependencies with Composer, you can run

```bash
vendor/bin/phpunit
```

[Click here for more details](tests/).

## Footnotes ##

I am certainly more familiar with SQL and relational data models so I think that may end up coming across in the code - rightly so or not!

Thanks to @sjlangley for any and all input - especially around unit tests for Protocol Buffers.

Whilst I am using this library in production, it is my hope that other people find it of use. Feedback appreciated.

# Other App Engine Software #

If you've enjoyed this, you might be interested in my [Full Text Search Library for PHP on Google App Engine](https://github.com/tomwalder/php-appengine-search)
