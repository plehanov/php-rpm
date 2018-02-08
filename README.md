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

Use it:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$spec = new \Plehanov\RPM\Spec();
$spec
    ->setProp([
        'Name', 'my-package-name',
        'Version' => '0.1.1',
        'Summary' => 'simple summary',
        'Release' => '1',
        'URL' => 'http://...',
    ])
    ->setProp('Version', '0.1.2')
    ->setBlock([
        'description', 'My software description',
    ])
    ->setBlock(
            'description', 'My software description'
    )
    ->setBlock('files', "%{buildroot}%{bindir}/binary\n%{buildroot}%{_libdir}/%{name}/*")
    ->setDefAttr(644, 'root', 'root', 755)
    ->addPerm('%{buildroot}%{bindir}/binary1', 644)
    ->addPerm('%{buildroot}%{bindir}/binary2', 644, 'apache')
    ->addPerm('%{buildroot}%{bindir}/binary3', 644, 'apache', 'jenkins');

$packager = new \Plehanov\RPM\Packager();

$packager->setOutputPath('/path/to/out');
$packager->setSpec($spec);

$packager->addMount('/path/to/source-conf', '/etc/my-sw');
$packager->addMount('/path/to/exec', '/usr/bin/my-sw');
$packager->addMount('/path/to/docs', '/usr/share/docs');

//Creates folders using mount points
$packager->run();

//Get the rpmbuild command
echo $packager->build();
```

**Create the Package**

```
$(php pack.php)
```
