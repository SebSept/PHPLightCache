Cache
=====

This is a lightweight file cache system. 

Features
========

- Lightweight (~8Kb)
- Simple to use (see API)
- 100% Unit tested
- Define expiration when retrieving content ( not when creating the cache )

Usage
=====

Sample here are not tested!

Basic
-----

```php
<?php
// include file (using composer)
require('vendor/autoload.php'); 
// or directly
require('./libs/PHPLightCacheFS/Cache.php');

// create an instance setting expiration delay (2 hours) and cache dir
$cache = new \SebSept\Cache(
array(
    'cacheDirectory' => '/tmp/cacheFS',
    'conditions' => array('max-age' => 60*60*2)
)
);

// create a cache
$cache->set('theCacheId', 'This is some cached content, string'));

// get cached content
$content = $cache->get('theCacheId');

// delete a cache
$cache->delete('theCacheId');
```

Other methods
-------------

```php

```

Advanced usage
--------------


```php

```

Installation
============

Composer
--------

NOT YET SUBMITTED TO composer/packagist

Add this to your `composer.json` :

```json
{
    "require": {
        "sebsept/cache": "1.0.*"
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
Expiration was not respected, so took a the code ... then decided to recode all.

License
=======

Published under [The MIT License (MIT)](./LICENCE).
