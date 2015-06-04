# Running Unit Tests #

Assuming you have PHPUnit installed and ready to roll...

Grab the Google SDK
`wget https://storage.googleapis.com/appengine-sdks/featured/google_appengine_1.9.21.zip`

Unzip it and store the path
`unzip google_app_engine_1.9.21.zip -d ~/`
`SDK_HOME=~/google_appengine/php/sdk`

Run PHPUnit, passing in the include path
`phpunit -d include_path=$SDK_HOME`