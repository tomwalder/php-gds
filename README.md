# Google Datastore Library for PHP #

[Google Cloud Datastore](https://cloud.google.com/datastore/) is a great NoSQL solution (hosted, scalable, free up to a point), but it can be tricky (i.e. there's lots of code glue needed) to get the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for you to get started with and to use Datastore in your applications.

## Basic Examples ##

I find examples a great way to decide if I want to even try out a library, so here's a couple for you.

I am skipping boilerplate code - these initial examples are just to show what working code might look like. I encourage you to check out the examples folder for full details.

Firstly, we'll need a concrete `GDS\Model` and `GDS\Store` class. See [Defining Your Model](#defining-your-model) below.

```php
class Book extends GDS\Model { /* No custom code needed */ }
class BookStore extends GDS\Store { /* Schema configuration */ }
```

Create a record and insert into the Datastore

```php
$obj_book = new Book();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Write it to Datastore
$obj_book_store->upsert($obj_book);
```

Now let's pull some data out of the Datastore

```php
// Fetch all the books and show their titles and ISBN
foreach($obj_book_store->fetchAll() as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
```

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

It often makes a lot of sense to define your Model Schema up front.  Because Datastore is schemaless the library also supports fields/properties that are not explicitly defined in your schema.  

Here is how we build the Schema (in the BookStore class) for our examples:

```php
// Define a schema with a Datastore Entity Kind of "Book" and 3 fields
$obj_schema = (new GDS\Schema('Book'))
   ->addString('title')
   ->addString('author')
   ->addString('isbn', TRUE);
```

In this example, the ISBN field has been specifically set as an indexed string field. By default, fields are string fields and are NOT indexed. 

See the `Schema` class for a list of supported types.

Take a look at the `examples` folder for a fully operational set of code.

### Re-indexing ###

When you change a field from non-indexed to indexed you will need to "re-index" all your existing entities before they will be returned in queries run against that index by Datastore. This is due to the way Google update their BigTable indexes.

I've included a simple example (paginated) re-index script in the examples folder, `reindex.php`.

## Queries, GQL & The Default Query ##

At the time of writing, the `GDS\Store` object uses Datastore GQL as it's query language and provides a few helper methods for some more common queries, like `fetchById()` and `fetchByName()`.

When you instanciate a store object, like `BookStore` in our example, it comes pre-loaded with a default GQL query of the following form

```sql
SELECT * FROM <Kind> ORDER BY __key__ ASC
```

Which means you can quickly and easily get one or all records without needing to write any GQL, like this:

```php
// Get the first record
$obj_book_store->fetchOne();

// Get all records
$obj_book_store->fetchAll();

// Get the first 10
$obj_book_store->fetchPage(10);
```

## Pagination ##

When working with larger data sets, it can be useful to page through results in smaller batches. Here's an example.

```php
// Set the GQL query, then fetch results in pages of 50 until we run out
$obj_book_store->query('SELECT * FROM Book');
while($arr_page = $obj_book_store->fetchPage(50)) {
    echo "Page contains ", count($arr_page), " records", PHP_EOL;
}
```

### Limits, Offsets & Cursors ###

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
$obj_google_client = GDS\Gateway::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);
$obj_customer_gateway = new GDS\Gateway($obj_google_client, GDS_DATASET_ID, 'customer-namespace');
$obj_customer_book_store = new BookStore($obj_customer_gateway);
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

