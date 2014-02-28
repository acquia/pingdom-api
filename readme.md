Pingdom API
===========

[![Build Status](https://travis-ci.org/acquia/pingdom-api.png?branch=master)](https://travis-ci.org/acquia/pingdom-api)

This package is a PHP interface to the Pingdom REST API.

Installation
------------

Run the following commands to install the required dependencies:

```bash
cd <project root>
composer install --no-dev
```

Usage
-----

```php
require 'vendor/autoload.php';
use Acquia\Pingdom\PingdomApi;

$pingdom = new PingdomApi('username', 'password', 'api_key');
print_r($pingdom->getChecks());
```

Running the tests
-----------------

The following commands can be used to run the test suite locally:

```bash
cd <project root>
composer update
phpunit
```

Using `composer update` without the `--no-dev` flag will download the phpunit
dependency.

