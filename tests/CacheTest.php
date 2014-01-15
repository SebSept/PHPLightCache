<?php
/**
 * SimpleFileCache Unit tests
 * Lightweight file cache provider
 *
 * @author  Sébastien Monterisi <sebastienmonterisi@yahoo.fr>
 * @link    https://github.com/SebSept/SimpleFileCache
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace SebSept\SimpleFileCache\tests;
use SebSept\SimpleFileCache\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    const CACHEDIRROOT = '/tmp/cacheRoot';
    const CACHEDIR = '/tmp/cacheRoot/cacheTests';
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

        // create test directories
        $this->prepareDirectory(self::CACHEDIRROOT);
        $this->prepareDirectory(self::CACHEDIR);
        $this->prepareDirectory(self::EXISTINGDIR);
        // create a non writable dir
        $nwd = self::NONWRITABLEDIR;
        $this->prepareDirectory($nwd);
        `chmod 555 $nwd`;

        // Creates cache object
        $this->cache = new Cache( array('directoryPath' => self::CACHEDIR) );
        // Checks 'testing' is not existing
        if ($this->cache->exists('testing')) {
            $cd = self::CACHEDIR;
            `rm -Rf $cd/*`;
        }
        $this->assertFalse($this->cache->exists('testing'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * valid path depth
     */
    public function testSetPathDepth_onValid()
    {
        $this->assertTrue($this->cache->setPathDepth(4));
    }

        /**
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * valid path depth
     */
    public function testSetPathDepth_onInvalid_tooLow()
    {
        $this->assertFalse($this->cache->setPathDepth(-1));
    }

        /**
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * valid path depth
     */
    public function testSetPathDepth_onInvalid_tooHigh()
    {
        $this->assertFalse($this->cache->setPathDepth(50));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::getFilePath
     * @covers SebSept\SimpleFileCache\Cache::setPathDepth
     * Default depth (5)
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
     * Check if configuration passed on constuctor is respected : pathDepth
     * @covers SebSept\SimpleFileCache\Cache::__construct()
     */
    public function testConstuctor_onConfigPassed_pathDepth()
    {
        // change cache dir
        $initialConfig = array('pathDepth' => 3, 'directoryPath' => self::CACHEDIR );
        $cache = new Cache($initialConfig);
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/mytestfilecache' ,
                $cache->getFilePath('mytestfilecache')
                );
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
     * @covers SebSept\SimpleFileCache\Cache::setDelay
     * SetDelay sets delay
     * delay set to 0 so get() must return null
     */
    public function testSetDelay()
    {
        $this->cache->set('testing', 'testSetDelay');
        $this->cache->setDelay(0);
        $this->assertNull($this->cache->get('testing'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDelay
     * delay set to 0 so get() must return null
     */
    public function testSetDelay_onParamIsInt()
    {
        $this->assertTrue($this->cache->setDelay(0));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDelay
     */
    public function testSetDelay_onParamIsString()
    {
        $this->assertTrue($this->cache->setDelay('12'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::setDelay
     */
    public function testSetDelay_onParamIsInvalidString()
    {
        $this->assertFalse($this->cache->setDelay('sdmfk'));
    }

    /**
     * @covers SebSept\SimpleFileCache\Cache::flush
     * cache deleted after flush()
     */
    public function testFlush()
    {
        $this->cache->set('testing', 'testFlush');
        $filePath = $this->cache->getFilePath('testing');
        $this->assertEquals('testFlush',$this->cache->get('testing'), 'Failled to run test, file must exists before testing flush()');
        $this->cache->flush();
        $this->assertNull($this->cache->get('testing'));
    }
    
    /**
     * @covers SebSept\SimpleFileCache\Cache::flush
     * flush() return true on success
     */
    public function testFlush_onSuccess()
    {
        $this->assertTrue($this->cache->flush());
    }
    
    /**
     * @covers SebSept\SimpleFileCache\Cache::flush
     * @expectedException Exception
     * flush() throw exception if failled to delete subdirs
     */
    public function testFlush_onFaillure()
    {
        $dir = $this->cache->getDirectoryPath();
        `chmod -w $dir/../`;
        $this->cache->flush();
    }
    
    /**
     * executed after each test to clear environnement
     */
    protected function tearDown()
    {
        $this->removeDirectory(self::CACHEDIRROOT);
//        $this->prepareDirectory(self::CACHEDIR);
        $this->removeDirectory(self::EXISTINGDIR);
        $this->removeDirectory(self::NONWRITABLEDIR);
    }
    
    /**
     * Remove cache dir created in test
     * @param string $dir
     */
    protected function removeDirectory($dir)
    {
        `chmod +w $dir -R`;
        `rm -Rf $dir`;
    }
    
    /**
     * Create directory for tests
     * 
     * @param string $dir new directory path
     * @return void
     */
    protected function prepareDirectory($dir)
    {
        if (!is_dir($dir)) {
            `mkdir $dir`;
        }
        // make it writable
        `chmod +w $dir`;
    }

}
