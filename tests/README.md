# Running Unit Tests #

Assuming you have PHPUnit installed and ready to roll... if not, [go here](https://phpunit.de) and do the thing! (like, install it!)

Grab the Google SDK

```bash
wget https://storage.googleapis.com/appengine-sdks/featured/google_appengine_1.9.21.zip
```

Unzip it and store the path

```bash
unzip google_appengine_1.9.21.zip -d ~/
SDK_HOME=~/google_appengine/php/sdk
```

Run PHPUnit, passing in the include path

```bash
phpunit -d include_path=$SDK_HOME
```
