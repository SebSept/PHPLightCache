<?php
/**
 * SimpleFileCache Unit tests
 * Lightweight file cache provider
 *
 * @author  Sébastien Monterisi (main author) <sebastienmonterisi@yahoo.fr>
 * @link    https://github.com/SebSept/SimpleFileCache
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace SebSept\SimpleFileCache\tests;
use SebSept\SimpleFileCache\Cache;

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
        $this->cache = new Cache( array('directoryPath' => self::CACHEDIR) );
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
     * @covers SebSept\SimpleFileCache\Cache::getFilePath
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * Default depth supposed to be 5
     */
    public function testgetFilePath_onDepthDefault()
    {
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/e/s/mytestfilecache' ,
                $this->cache->getFilePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::getFilePath
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     */
    public function testgetFilePath_onDepth3()
    {
        $this->cache->setPathDepth(3);
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/mytestfilecache' ,
                $this->cache->getFilePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::getFilePath
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * Default depth will be default, 5
     */
    public function testgetFilePath_onDepthInvalid()
    {
        $this->cache->setPathDepth('stupid val');
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/e/s/mytestfilecache' ,
                $this->cache->getFilePath('mytestfilecache')
                );
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDirectoryPath
     * @expectedException Exception
     */
    public function testsetDirectoryPath_onExistingNonWritableDir()
    {
        $dir = self::EXISTINGDIR;
        `chmod -w $dir`;
        $this->cache->setDirectoryPath(self::EXISTINGDIR);
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDirectoryPath
     * @expectedException Exception
     */
    public function testsetDirectoryPath_onNonExistingDir()
    {
        $this->cache->setDirectoryPath(self::NONEXISTINGDIR);
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDirectoryPath
     */
    public function testsetDirectoryPath_onExistingDir()
    {
        $this->assertTrue($this->cache->setDirectoryPath(self::EXISTINGDIR));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::getDirectoryPath
     */
    public function testgetDirectoryPath_onDefault()
    {
        $this->assertEquals( self::CACHEDIR , $this->cache->getDirectoryPath());
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::getDirectoryPath
     */
    public function testgetDirectoryPath_onChangedExisting()
    {
        $this->cache->setDirectoryPath(self::EXISTINGDIR);
        $this->assertEquals(self::EXISTINGDIR, $this->cache->getDirectoryPath());
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::set
     * @covers SebSept\SimpleFileCache\Cache::createCacheDir
     * @covers SebSept\SimpleFileCache\Cache::checkValidCacheId
     */
    public function testSet_onWritableCacheDir()
    {
        $this->assertTrue($this->cache->set('mycacheid','thecontent'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::set
     * @covers SebSept\SimpleFileCache\Cache::createCacheDir
     * @covers SebSept\SimpleFileCache\Cache::checkValidCacheId
     * @expectedException Exception
     * Must throw on exception if chars others than alpha and digits
     */
    public function testSet_onCacheIdWithSpecialChars()
    {
        $this->cache->set('?././*y..ac?eid','thecontent!');
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     */
    public function testExits_onDefaultDelay()
    {
        $this->cache->set('testing', 'testExits_onNoDelay');
        $this->assertTrue($this->cache->exists('testing'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     * Data should be cached
     */
    public function testExits_onValidDelay()
    {
        $this->cache->set('testing', 'testExits_onValidDelay');
        $this->assertTrue($this->cache->exists('testing', 60));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     * Cache expired
     */
    public function testExits_onExpiredDelay()
    {
        $this->cache->set('testing', 'testExits_onExpiredDelay');
        sleep(2);
        $this->assertFalse($this->cache->exists('testing', 1));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     * Cache expired - -1 second
     */
    public function testExits_onDelayIsNegative()
    {
        $this->cache->set('testing', 'testExits_onDelayIsNegative');
        $this->assertFalse($this->cache->exists('testing', -1));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     * Cache expired - 0 second
     * 0 second proprably means 'no cache'
     */
    public function testExits_onDelayIsZero()
    {
        $this->cache->set('testing', 'testExits_onDelayIsZero');
        $this->assertFalse($this->cache->exists('testing', 0));
    }

   /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     */
    public function testGet_onUndefinedCacheId()
    {
        $this->assertNull( $this->cache->get('undefined'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::get
     * @covers SebSept\SimpleFileCache\Cache::exists
     * @covers SebSept\SimpleFileCache\Cache::isExpired
     * Delay make the cache expired, must return NULL
     */
    public function testGet_onDefinedCacheId_withDelay()
    {
        $this->cache->set('testing', 'testGet_onDefinedCacheId_withDelay');
        $this->assertNull( $this->cache->get('testing', 0) );
    }

    /**
     * Check if configuration passed on constuctor is respected : delay
     * @covers SebSept\SimpleFileCache\Cache::__construct()
     * because of delay:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_delay()
    {
        // config : no cache
        $initialConfig = array('delay' => 0, 'directoryPath' => self::CACHEDIR );
        $cache = new Cache($initialConfig);
        $cache->set('testing', 'testConstuctor_onConfigPassed_delay');
        $this->assertFalse($cache->exists('testing'));
    }

    /**
     * Check if configuration passed on constuctor is respected : cachedir
     * @covers SebSept\SimpleFileCache\Cache::__construct()
     * because of delay:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_cachedir()
    {
        // change cache dir
        $initialConfig = array('directoryPath' => self::EXISTINGDIR );
        $cache = new Cache($initialConfig);
        $this->assertEquals(self::EXISTINGDIR, $cache->getDirectoryPath());
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::delete
     */
    public function testDelete_onExistingCache_Removable()
    {
        $this->cache->set('acache', 'testDelete_onExistingCache_Removable');
        $this->assertTrue( $this->cache->delete('acache'));
        $this->assertNull($this->cache->get('acache'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::delete
     * @expectedException Exception
     * Exception if failed to delete cache
     */
    public function testDelete_onExistingCache_NotRemovable()
    {
        $this->cache->set('acache', 'testDelete_onExistingCache_Removable');
        // prevent file from being deletable by changing last dir rights
        $dir = dirname($this->cache->getFilePath('acache'));
        `chmod -w $dir`;

        $this->cache->delete('acache');
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::delete
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
        $cd = $this->cache->getDirectoryPath();
        `chmod +w $cd -R`;
        `rm -Rf $cd`;
    }

}
