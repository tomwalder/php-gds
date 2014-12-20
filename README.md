# Google Datastore Library for PHP #

Google Cloud Datastore is a great NoSQL solution, but it can be tricky (i.e. there's lots of glue) to get the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for you to get started and to use Datastore in your applications.

I am certainly more familiar with SQL and relational data models so I think that may end up coming across in the code - rightly so or not!

## Example Usage ##

I find examples a great way to decide if I want to use a library, so here's one for you (without the boilerplate)...

```php
// Let's create a new Book object with some data
$obj_book = new Book();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// And write it to Datastore
$obj_book_repo->insert($obj_book);
```

Now let's pull some data out of Datastore

```php
$arr_books = $obj_book_repo->query("SELECT * FROM Book");
foreach($arr_books as $obj_book) {
    echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
```

## What is Google Cloud Datastore? ##

https://cloud.google.com/datastore/

## Getting Started ##

You'll need 
- a Google Account (doh)
- a Project to work on with the "Google Cloud Datastore API" turned ON in your project (https://console.developers.google.com/)
- a "Service account" and a P12 key file for that service account (https://developers.google.com/accounts/docs/OAuth2#serviceaccount)

## Running the examples ##

You will need to create 2 files in the `examples/config` folder as follows
- `config.php` (you can use `_config.php` as a template)
- `key.p12`

Or, you can pass in your own `Google_Client` object, configured with whatever auth you like.


## Footnote ##

Not that it matters too much, I've been trying to decide what sort of Patterns this library contains. See PEAA here: http://martinfowler.com/eaaCatalog/index.html 

What I decided is that I'm not really following DataMapper or Repository to the letter of how they were envisaged.

Probably it's closest to DataMapper. 
