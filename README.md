RPM packager (PHP)
==================

[![Latest Stable Version](https://poser.pugx.org/plehanov/php-rpm/v/stable)](https://packagist.org/packages/plehanov/php-rpm)
[![Total Downloads](https://poser.pugx.org/plehanov/php-rpm/downloads)](https://packagist.org/packages/plehanov/php-rpm) [![License](https://poser.pugx.org/plehanov/php-rpm/license)](https://packagist.org/packages/plehanov/php-rpm)
[![Code Climate](https://codeclimate.com/github/plehanov/php-rpm/badges/gpa.svg)](https://codeclimate.com/github/plehanov/php-rpm)
[![Issue Count](https://codeclimate.com/github/plehanov/php-rpm/badges/issue_count.svg)](https://codeclimate.com/github/plehanov/php-rpm)

A simple rpm packager for PHP applications.

Get composer:

```
curl -sS http://getcomposer.org/installer | php
```

Install dependencies and autoloader

```
php composer.phar install
//or
composer require plehanov/php-rpm
```

Use it: pack.php

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$spec = new \Plehanov\RPM\Spec();
$spec
    // Set many properties
    ->setProp([
        'Name', 'my-package-name',
        'Version' => '0.1.1',
        'Summary' => 'simple summary',
        'Release' => '1',
        'URL' => 'http://...',
    ])
    // Set single property
    ->setProp('Version', '0.1.2')
    // Set many options
    ->setBlock([
        'description', 'My software description',
    ])
    // Set single option
    ->setBlock('description', 'My software description')    
    // Default permission: file mode, user, group, folder mode
    ->setDefAttr(644, 'root', 'root', 755)
    ->addPerm('/etc/package1/bin/run', 644)
    ->addPerm('/etc/package1/source', 644, 'apache')
    // Custom permission: mode, user, group
    ->addPerm('/etc/package1/lib', 644, 'apache', 'jenkins');

$packager = new \Plehanov\RPM\Packager();
// Build temporary folder
$packager->setOutputPath('/path/to/out');
$packager->setSpec($spec);

// Copy file /path-from/source-conf to /etc/package1/source/main.conf
$packager->addMount('/path-from/source-conf', '/etc/package1/source/main.conf');
// Copy file /path-from/binary to /etc/package1/bin/run
$packager->addMount('/path-from/binary', '/etc/package1/bin/run');
// Copy folder /path-from/library/ to /etc/package1/lib/
$packager->addMount('/path-from/library', '/etc/package1/lib');
// Copy folder /path-from/library2/ to /etc/package1/lib2/
$packager->addMount('/path-from/library2/', '/etc/package1/lib2/');

//Creates folders using mount points
$packager->run();

//Get the rpmbuild command
echo $packager->build();
```

**Create the Package**

```
$(php pack.php)
```
