# Landa DB

Simple PHP Mysql Migration

## Installation

Install with [Composer](http://getcomposer.org/)

Add `cahkampung/landa-db` to require in composer.json

`"require": { "cahkampung/landa-db": "^1.0" },`

or run in terminal 

`composer require cahkampung/landa-migrasi`

Run `composer install`

## Example To Use

After add landa-migrasi, create php file with name index.php :

```
use Cahkampung\Migrasi;

require 'vendor/autoload.php';

$db_setting = [
    "host"     => "localhost",
    "username" => "root",
    "password" => "qwerty",
    "database" => "landa_sampang_pengajuan",
    "path"     => "migrasi",
];

$migrasi = new Migrasi($db_setting);

$migrasi->migrasi();
```

Run index.php 
