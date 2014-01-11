Cache
=====

This is a lightweight file cache system. 

Currently in beta stage, do not yet use it on production !

Features
========

- Lightweight (~8Kb)
- Simple to use (see API)
- 100% Unit tested
- Define expiration when retrieving content ( not when creating the cache )

Usage
=====

Basic
-----

```php
<?php
// include file (using composer)
require('vendor/autoload.php');

// create an instance setting expiration delay (2 hours) and cache dir
$cache = new \SebSept\Cache\Cache(
array(
    'cacheDirectory' => '/tmp/cacheFS',
    'delay' => 60*60*2
)
);

// create a cache
$cache->set('theCacheId', 'This is some cached content, string');

// get cached content
$content = $cache->get('theCacheId');

// delete a cache
$cache->delete('theCacheId');
```

Other methods
-------------

```php
// check that a cache with id 'theCacheId' exists and is less than an hour
$cache->exists('theCacheId', array('delay' => 60*60));

// full path to file on disk
$pathToFile = $cache->getCachePath('theCacheId');

// path to base dir where cache files are
$cacheDirPath = $cache->getCacheDirectory();
```

```php
// change path depth
$cache->setPathDepth(3); // files will be stored in /CacheDir/1/2/3/thecachename

// change cache directory
$cache->setCacheDirectory('/tmp/newdir'); 
```

Installation
============

Composer
--------

Add this to your `composer.json` :

```json
{
    "require": {
        "sebsept/cache": "dev-master"
    }
}
```

Git
---

```bash
git clone https://github.com/SebSept/PHPLightCacheFS.git
```

Direct download
---------------

* [Download .zip](https://github.com/SebSept/PHPLightCacheFS/archive/master.zip)
* [Download .tar.gz](https://github.com/SebSept/PHPLightCacheFS/archive/master.tar.gz)

History
=======

This is a fork of https://github.com/Gregwar/Cache . 
I found and install Gregwar/Cache from composer but found a bug :
Expiration was not respected, so took a look at the code ... then decided to recode it all.

License
=======

Published under [The MIT License (MIT)](./LICENSE).
