Running the tests locally
=========================

The following commands can be used to run the test suite locally:

    cd <project root>
    composer update
    vendor/phpunit/phpunit/phpunit.php --bootstrap test/bootstrap.php test/Acquia/Test/PingdomApi.php

Using `composer update` will download the phpunit dependency which should not be
committed. To get the repository back to its previous state run:

    git reset --hard

