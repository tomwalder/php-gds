# Google Datastore Library for PHP #

Google Datastore (GDS from here on out) is awesome, but it can be tricky (i.e. there's lots of glue) to get the "Hello World" of data persistence up and running in PHP.

This library is intended to make it easier for people to get started.

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