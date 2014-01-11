<?php
/**
 * PHPLightCacheFS Unit tests
 * Lightweight file cache provider
 *
 * @author  Sébastien Monterisi (main author) <sebastienmonterisi@yahoo.fr>
 * @link    https://github.com/SebSept/PHPLightCacheFS
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace SebSept\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    const CACHEDIR = '/tmp/cacheTests';
    const NONEXISTINGDIR = '/dev/null/doenstexist';
    const EXISTINGDIR = '/tmp/another';
    const NONWRITABLEDIR = '/tmp/nonWritable';

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * - create cache dir if doesn't exists : CACHEDIR & EXISTINGDIR
     * - Creates cache object
     * - Checks 'testing' is not existing
     * - create a non writable dir
     */
    protected function setUp()
    {
        // set env debug mode - set to true only to find problem
        $_ENV['debug'] = false;

        // create cache dir if doesn't exists
        $nd = self::CACHEDIR;
        if (!is_dir($nd)) {
            `mkdir $nd`;
        }
        // make it writable
        `chmod +w $nd`;

        // same for EXISTINGDIR
        $nd = self::EXISTINGDIR;
        if (!is_dir($nd)) {
            `mkdir $nd`;
        }
        // make it writable
        `chmod +w $nd`;

        // Creates cache object
        $this->cache = new Cache( array('cacheDirectory' => self::CACHEDIR) );

        // Checks 'testing' is not existing
        if ($this->cache->exists('testing')) {
            $cd = self::CACHEDIR;
            `rm -Rf $cd/*`;
        }
        $this->assertFalse($this->cache->exists('testing'));

        // create a non writable dir
        $nwd = self::NONWRITABLEDIR;
        if (!is_dir(self::NONWRITABLEDIR)) {
            `mkdir $nwd`;
        }
        `chmod 555 $nwd`;
    }

    /**
     * @covers SebSept\Cache\Cache::getCachePath
     * @covers SebSept\Cache\Cache::setPathDepth
     * Default depth supposed to be 5
     */
    public function testGetCachePath_onDepthDefault()
    {
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/e/s/mytestfilecache' ,
                $this->cache->getCachePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\Cache\Cache::getCachePath
     * @covers SebSept\Cache\Cache::setPathDepth
     */
    public function testGetCachePath_onDepth3()
    {
        $this->cache->setPathDepth(3);
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/mytestfilecache' ,
                $this->cache->getCachePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\Cache\Cache::getCachePath
     * @covers SebSept\Cache\Cache::setPathDepth
     * Default depth will be default, 5
     */
    public function testGetCachePath_onDepthInvalid()
    {
        $this->cache->setPathDepth('stupid val');
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/e/s/mytestfilecache' ,
                $this->cache->getCachePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\Cache\Cache::setCacheDirectory
     * @expectedException Exception
     */
    public function testSetCacheDirectory_onExistingNonWritableDir()
    {
        $dir = self::EXISTINGDIR;
        `chmod -w $dir`;
        $this->cache->setCacheDirectory(self::EXISTINGDIR);
    }
    
    /**
     * @covers SebSept\Cache\Cache::setCacheDirectory
     * @expectedException Exception
     */
    public function testSetCacheDirectory_onNonExistingDir()
    {
        $this->cache->setCacheDirectory(self::NONEXISTINGDIR);
    }

    /**
     * @covers SebSept\Cache\Cache::setCacheDirectory
     */
    public function testSetCacheDirectory_onExistingDir()
    {
        $this->assertTrue($this->cache->setCacheDirectory(self::EXISTINGDIR));
    }

    /**
     * @covers SebSept\Cache\Cache::getCacheDirectory
     */
    public function testGetCacheDirectory_onDefault()
    {
        $this->assertEquals( self::CACHEDIR , $this->cache->getCacheDirectory());
    }

    /**
     * @covers SebSept\Cache\Cache::getCacheDirectory
     */
    public function testGetCacheDirectory_onChangedExisting()
    {
        $this->cache->setCacheDirectory(self::EXISTINGDIR);
        $this->assertEquals(self::EXISTINGDIR, $this->cache->getCacheDirectory());
    }

    /**
     * @covers SebSept\Cache\Cache::set
     * @covers SebSept\Cache\Cache::createCacheDir
     * @covers SebSept\Cache\Cache::checkValidCacheId
     */
    public function testSet_onWritableCacheDir()
    {
        $this->assertTrue($this->cache->set('mycacheid','thecontent'));
    }

    /**
     * @covers SebSept\Cache\Cache::set
     * @covers SebSept\Cache\Cache::createCacheDir
     * @covers SebSept\Cache\Cache::checkValidCacheId
     * @expectedException Exception
     * Must throw on exception if chars others than alpha and digits
     */
    public function testSet_onCacheIdWithSpecialChars()
    {
        $this->cache->set('?././*y..ac?eid','thecontent!');
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     */
    public function testExits_onDefaultDelay()
    {
        $this->cache->set('testing', 'testExits_onNoDelay');
        $this->assertTrue($this->cache->exists('testing'));
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     * Data should be cached
     */
    public function testExits_onValidDelay()
    {
        $this->cache->set('testing', 'testExits_onValidDelay');
        $this->assertTrue($this->cache->exists('testing', 60));
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     * Cache expired
     */
    public function testExits_onExpiredDelay()
    {
        $this->cache->set('testing', 'testExits_onExpiredDelay');
        sleep(2);
        $this->assertFalse($this->cache->exists('testing', 1));
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     * Cache expired - -1 second
     */
    public function testExits_onDelayIsNegative()
    {
        $this->cache->set('testing', 'testExits_onDelayIsNegative');
        $this->assertFalse($this->cache->exists('testing', -1));
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     * Cache expired - 0 second
     * 0 second proprably means 'no cache'
     */
    public function testExits_onDelayIsZero()
    {
        $this->cache->set('testing', 'testExits_onDelayIsZero');
        $this->assertFalse($this->cache->exists('testing', 0));
    }

   /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     */
    public function testGet_onUndefinedCacheId()
    {
        $this->assertNull( $this->cache->get('undefined'));
    }

    /**
     * @covers SebSept\Cache\Cache::get
     * @covers SebSept\Cache\Cache::exists
     * @covers SebSept\Cache\Cache::checkConditions
     * Delay make the cache expired, must return NULL
     */
    public function testGet_onDefinedCacheId_withDelay()
    {
        $this->cache->set('testing', 'testGet_onDefinedCacheId_withDelay');
        $this->assertNull( $this->cache->get('testing', 0) );
    }

    /**
     * Check if configuration passed on constuctor is respected : delay
     * @covers SebSept\Cache\Cache::__construct()
     * because of delay:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_delay()
    {
        // config : no cache
        $initialConfig = array('delay' => 0, 'cacheDirectory' => self::CACHEDIR );
        $cache = new Cache($initialConfig);
        $cache->set('testing', 'testConstuctor_onConfigPassed_delay');
        $this->assertFalse($cache->exists('testing'));
    }

    /**
     * Check if configuration passed on constuctor is respected : cachedir
     * @covers SebSept\Cache\Cache::__construct()
     * because of delay:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_cachedir()
    {
        // change cache dir
        $initialConfig = array('cacheDirectory' => self::EXISTINGDIR );
        $cache = new Cache($initialConfig);
        $this->assertEquals(self::EXISTINGDIR, $cache->getCacheDirectory());
    }

    /**
     * @covers SebSept\Cache\Cache::delete
     */
    public function testDelete_onExistingCache_Removable()
    {
        $this->cache->set('acache', 'testDelete_onExistingCache_Removable');
        $this->assertTrue( $this->cache->delete('acache'));
        $this->assertNull($this->cache->get('acache'));
    }

    /**
     * @covers SebSept\Cache\Cache::delete
     * @expectedException Exception
     * Exception if failed to delete cache
     */
    public function testDelete_onExistingCache_NotRemovable()
    {
        $this->cache->set('acache', 'testDelete_onExistingCache_Removable');
        // prevent file from being deletable by changing last dir rights
        $dir = dirname($this->cache->getCachePath('acache'));
        `chmod -w $dir`;

        $this->cache->delete('acache');
    }

    /**
     * @covers SebSept\Cache\Cache::delete
     * returns true on non existing cache
     */
    public function testDelete_onNonExistingCache()
    {
        $this->assertTrue( $this->cache->delete('acache'));
    }

    /**
     * executed after each test to clear environnement
     */
    protected function tearDown()
    {
        // remove cache dir created in test
        $cd = $this->cache->getCacheDirectory();
        `chmod +w $cd -R`;
        `rm -Rf $cd`;
    }

}
