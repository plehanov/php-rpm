RPM packager (PHP)
==================

A simple rpm packager for PHP applications.

Get composer:

```
curl -sS http://getcomposer.org/installer | php
```

Install dependencies and autoloader

```
php composer.phar install
//or
composer require plehanov/php-rpm-packager
```

Use it:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$spec = new \plehanov\rpm\Spec();
$spec
    ->setProp([
        'Name', 'my-package-name',
        'Version' => '0.1.1',
        'Summary' => 'simple summary',
        'Release' => '1',
        'URL' => 'http://...',
    ])
    ->setBlock([
        'description', 'My software description',
    ]);

$packager = new \plehanov\rpm\Packager();

$packager->setOutputPath("/path/to/out");
$packager->setSpec($spec);

$packager->addMount("/path/to/source-conf", "/etc/my-sw");
$packager->addMount("/path/to/exec", "/usr/bin/my-sw");
$packager->addMount("/path/to/docs", "/usr/share/docs");

//Creates folders using mount points
$packager->run();

//Get the rpmbuild command
echo $packager->build();
```

**Create the Package**

```
$(php pack.php)
```
