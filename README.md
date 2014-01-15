Cache
=====

This is a light weight file cache component for php. 

Features
========

- Lightweight (~8Kb)
- Simple to use (see API)
- 100% Unit tested
- Optionnaly define expiration delay when retrieving content ( not when creating the cache )

Usage
=====

Basic
-----

```php
<?php
// include file (using composer)
require('vendor/autoload.php');

// create an instance of Cache() with default params
$cache = new \SebSept\SimpleFileCache\Cache();

// create an instance, setting expiration delay (2 hours) 
// and cache dir and cache dir depth
$cache = new \SebSept\SimpleFileCache\Cache(
array(
    'directoryPath' => '/tmp/cacheFS',
    'delay' => 60*60*2, 
    'pathDepth' => 2
)
);

// create a cache
$cache->set('theCacheId', 'This is some cached content, string');

// get cached content
$content = $cache->get('theCacheId');

// get cached content not older than 120 secondes
$content = $cache->get('theCacheId', 120); // content is null if cache expired or not existing


// delete a cache
$cache->delete('theCacheId');
```

Other methods
-------------

```php
// check that a cache with id 'theCacheId' exists and is less than an hour
$cache->exists('theCacheId', array('delay' => 60*60));

// full path to file on disk
$pathToFile = $cache->getFilePath('theCacheId');

// path to base dir where cache files are
$cacheDirPath = $cache->getDirectoryPath();
```

```php
// change path depth
$cache->setPathDepth(3); // files will be stored in /CacheDir/1/2/3/thecachename

// change cache directory
$cache->setDirectoryPath('/tmp/newdir'); 
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
git clone https://github.com/SebSept/SimpleFileCache.git
```

Direct download
---------------

* [Download .zip](https://github.com/SebSept/SimpleFileCache/archive/master.zip)
* [Download .tar.gz](https://github.com/SebSept/SimpleFileCache/archive/master.tar.gz)

History
=======

This is a fork of https://github.com/Gregwar/Cache . 
I found and install Gregwar/Cache from composer but found a bug :
Expiration was not respected, so took a look at the code ... then decided to recode it all.

License
=======

Published under [The MIT License (MIT)](./LICENSE).
