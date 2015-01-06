# Google Datastore Library for PHP #

[Google Cloud Datastore](https://cloud.google.com/datastore/) is a great NoSQL solution (hosted, scalable, free up to a point), but it can be tricky (i.e. there's lots of code glue needed) to get the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for you to get started with and to use Datastore in your applications.

## Basic Examples ##

I find examples a great way to decide if I want to even try out a library, so here's a couple for you. Check out the examples folder for full code samples.

Firstly, we'll need a `GDS\Store` through which we will read and write `GDS\Model` objects to and from Datastore. 

The Store needs a `GDS\Gateway` to talk to Google. The gateway needs a `Google_Client` for authentication.

```php
$obj_client = GDS\Gateway::createGoogleClient(APP_NAME, ACCOUNT_NAME, KEY_FILE);
$obj_gateway = new GDS\Gateway($obj_client, DATASET_ID);
$obj_book_store = new GDS\Store($obj_gateway, 'Book');
```

Create a record and insert into the Datastore (see below for [Alternative Array Syntax](#alternative-array-syntax)) 

```php
$obj_book = new GDS\Model();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Write it to Datastore
$obj_book_store->upsert($obj_book);
```

Fetch all the Books from the Datastore and display their titles and ISBN numbers

```php
foreach($obj_book_store->fetchAll() as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
```

These examples use the generic `GDS\Model` class with a dynamic Schema. See [Defining Your Model](#defining-your-model) below for more details on custom Models, Schemas and indexed fields.

## Getting Started ##

Are you sitting comfortably? before we begin, you will need: 
- a Google Account (doh)
- a Project to work on with the "Google Cloud Datastore API" turned ON [Google Developer Console](https://console.developers.google.com/)
- a "Service account" and a P12 key file for that service account [Service Accounts](https://developers.google.com/accounts/docs/OAuth2#serviceaccount)

### Composer, Dependencies ###

To install using composer, use this require line

`"tomwalder/php-gds": "dev-master"`

I use the Google php api client for low-level access to Datastore services, so that will get pulled in to your project too.

### Running the examples ###

You will need to create 2 files in the `examples/config` folder as follows
- `config.php` (you can use `_config.php` as a template)
- `key.p12`

Or, you can pass in your own `Google_Client` object, configured with whatever auth you like.

## Defining Your Model ##

Because Datastore is schemaless, the library also supports fields/properties that are not explicitly defined. But it often makes a lot of sense to define your Model Schema up front.

Here is how we might build the Schema for our examples, with a Datastore Entity Kind of "Book" and 3 fields.

```php
$obj_schema = (new GDS\Schema('Book'))
   ->addString('title')
   ->addString('author')
   ->addString('isbn', TRUE);
   
// The Store class accepts either a Schema object or Kind name as it's second parameter
$obj_book_store = new GDS\Store($obj_gateway, $obj_schema);
```

In this example, the ISBN field has been specifically set as an indexed string field. By default, fields are string fields and are NOT indexed. An indexed field can be used in a WHERE clause.

Avaialable Schema configuration methods:
- `GDS\Schema::addString`
- `GDS\Schema::addInteger`
- `GDS\Schema::addDatetime`
- `GDS\Schema::addFloat`
- `GDS\Schema::addBoolean`
- `GDS\Schema::addStringList`

Take a look at the `examples` folder for a fully operational set of code.

### Custom Models and Stores ###

Whilst you can use the `GDS\Model` and `GDS\Store` classes directly, as per the examples above, you may find it useful to extend both and have the extended Store contain the Schema definition.

For example

```php
class Book extends GDS\Model { /* ... */ }
class BookStore extends GDS\Store { /* ... */ }
```

This way, when you pull objects out of Datastore, they are objects of your defined Model class.

```php
$obj_store = new BookStore($obj_gateway);
$obj_book = $obj_store->fetchOne(); // $obj_book will be a "Book" object
```

Check out the examples folder for `Book.php` and `BookStore.php` code samples.

### Re-indexing ###

When you change a field from non-indexed to indexed you will need to "re-index" all your existing entities before they will be returned in queries run against that index by Datastore. This is due to the way Google update their BigTable indexes.

I've included a simple example (paginated) re-index script in the examples folder, `reindex.php`.

## Creating Records ##

### Alternative Array Syntax ###

There is an alternative to directly constructing a new `GDS\Model` and setting it's member data, which is to use the `GDS\Store::createFromArray` factory method as follows.

```php
$obj_book = $obj_book_store->createFromArray([
    'title' => 'The Merchant of Venice',
    'author' => 'William Shakespeare',
    'isbn' => '1840224312'
]);
```

## Queries, GQL & The Default Query ##

At the time of writing, the `GDS\Store` object uses Datastore GQL as it's query language. Here is an example:

```php
$obj_book = $obj_book_store->fetchOne("SELECT * FROM Book WHERE isbn = '1853260304'");
```
 
We provide a few helper methods for some more common queries, like `fetchById()` and `fetchByName()`.

When you instantiate a store object, like `BookStore` in our example, it comes pre-loaded with a default GQL query of the following form

```sql
SELECT * FROM <Kind> ORDER BY __key__ ASC
```

Which means you can quickly and easily get one or many records without needing to write any GQL, like this:

```php
$obj_store->fetchOne();
```

Get all books

```php
$obj_store->fetchAll();
```

Get the first 10 books

```php
$obj_store->fetchPage(10);
```

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

This is all fixed by using Cursors. The implementation is all encapsulated within the `Gateway` class so you don't need to worry about it.

#### Tips for LIMIT-ed fetch operations ####

Do not supply a `LIMIT` clause when calling 
- `fetchOne()` - it's done for you (we add `LIMIT 1`)
- `fetchPage()` - again, it's done for you and it will cause a conflict. 

#### Pricing & Cursor References ####

- [Query Cursors](https://cloud.google.com/datastore/docs/concepts/queries#Datastore_Query_cursors)
- [Costs for Datastore Calls](https://cloud.google.com/appengine/pricing)
- [Datastore Quotas](https://cloud.google.com/appengine/docs/quotas#Datastore)

## Multi-tenant Applications & Data Namespaces ##

Google Datastore supports segregating data within a single "Dataset" using something called Namespaces.

Generally, this is intended for multi-tenant applications where each customer would have separate data, even within the same "Kind".

This library supports namespaces, and they can be configured per `Gateway` instance ny passing in the optional namespace parameter.

```php
// Create a store for a particular customer or 'application namespace'
$obj_client = GDS\Gateway::createGoogleClient(APP_NAME, ACCOUNT_NAME, KEY_FILE);
$obj_namespaced_gateway = new GDS\Gateway($obj_client, DATASET_ID, 'customer-namespace');
$obj_namespaced_book_store = new BookStore($obj_namespaced_gateway);
```

Further examples are included in the examples folder.

## More About Google Cloud Datastore ##

What Google say:

> "Use a managed, NoSQL, schemaless database for storing non-relational data. Cloud Datastore automatically scales as you need it and supports transactions as well as robust, SQL-like queries."

https://cloud.google.com/datastore/

### Specific Topics ###

A few highlighted topics you might want to read up on
- [Entities, Data Types etc.](https://cloud.google.com/datastore/docs/concepts/entities)
- [More information on GQL](https://cloud.google.com/datastore/docs/concepts/gql)
- [Indexes](https://cloud.google.com/datastore/docs/concepts/indexes)

## Footnotes ##

I am certainly more familiar with SQL and relational data models so I think that may end up coming across in the code - rightly so or not!

I've been trying to decide if & what sort of Patterns this library contains. [PEAA](http://martinfowler.com/eaaCatalog/index.html). What I decided is that I'm not really following DataMapper or Repository to the letter of how they were envisaged. Probably it's closest to DataMapper. 

