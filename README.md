# Google Datastore Library for PHP #

[Google Cloud Datastore](https://cloud.google.com/datastore/) is a great NoSQL solution, but it can be tricky (i.e. there's lots of code glue needed) to get the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for you to get started with and to use Datastore in your applications.

## Basic Examples ##

I find examples a great way to decide if I want to even try out a library, so here's a couple for you (without the boilerplate)...

```php
// Let's create a new Book object with some data
$obj_book = new Book();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// And write it to Datastore
$obj_book_store->upsert($obj_book);
```

Now let's pull some data out of Datastore

```php
// Fetch all the books, using a GQL query, and show their titles and ISBN
$arr_books = $obj_book_store->query("SELECT * FROM Book");
foreach($arr_books as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
```

## Getting Started ##

Are you sitting comfortable? before we begin, you will need: 
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

"Use a managed, NoSQL, schemaless database for storing non-relational data. Cloud Datastore automatically scales as you need it and supports transactions as well as robust, SQL-like queries."

https://cloud.google.com/datastore/

### Specific Topics ###
Some highlighted topics you might want to read up on
- [Entities, Data Types etc.](https://cloud.google.com/datastore/docs/concepts/entities)
- [More information on GQL](https://cloud.google.com/datastore/docs/concepts/gql)
- [Indexes](https://cloud.google.com/datastore/docs/concepts/indexes)

## Footnotes ##

I am certainly more familiar with SQL and relational data models so I think that may end up coming across in the code - rightly so or not!

Not that it matters too much, I've been trying to decide what sort of Patterns this library contains. [PEAA](http://martinfowler.com/eaaCatalog/index.html).

What I decided is that I'm not really following DataMapper or Repository to the letter of how they were envisaged.

Probably it's closest to DataMapper. 
