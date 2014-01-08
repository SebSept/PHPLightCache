<?php

namespace Gregwar\Cache;


/**
 * Unit testing for Cache
 */
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
        if(!is_dir(self::CACHEDIR))
        {
            $nd = self::CACHEDIR;
            `mkdir $nd`;
        }

        // same for EXISTINGDIR
        if(!is_dir(self::EXISTINGDIR))
        {
            $nd = self::EXISTINGDIR;
            `mkdir $nd`;
        }
        
        // Creates cache object
        $this->cache = new Cache( array('cacheDirectory' => self::CACHEDIR) );
        
        // Checks 'testing' is not existing
        if($this->cache->exists('testing'))
        {
            $cd = self::CACHEDIR;
            `rm -Rf $cd/*`;
        }
        $this->assertFalse($this->cache->exists('testing'));
        
        // create a non writable dir
        $nwd = self::NONWRITABLEDIR;
        if(!is_dir(self::NONWRITABLEDIR))
        {
            `mkdir $nwd`;
        }
        `chmod 555 $nwd`;
    }
    
    /**
     * @covers Gregwar\Cache\Cache::getCachePath
     * @covers Gregwar\Cache\Cache::setPathDepth
     * Default depth supposed to be 5
     */
    public function testGetCachePath_onDepthDefault() 
    {
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/e/s/mytestfilecache' ,
                $this->cache->getCachePath('mytestfilecache') 
                );
    }
    
    /**
     * @covers Gregwar\Cache\Cache::getCachePath
     * @covers Gregwar\Cache\Cache::setPathDepth
     */
    public function testGetCachePath_onDepth3() 
    {
        $this->cache->setPathDepth(3);
        $this->AssertEquals(self::CACHEDIR.'/m/y/t/mytestfilecache' ,
                $this->cache->getCachePath('mytestfilecache') 
                );
    }
    
    /**
     * @covers Gregwar\Cache\Cache::getCachePath
     * @covers Gregwar\Cache\Cache::setPathDepth
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
     * @covers Gregwar\Cache\Cache::setCacheDirectory
     */
    public function testSetCacheDirectory_onNonExistingDir() 
    {
        $this->assertFalse($this->cache->setCacheDirectory(self::NONEXISTINGDIR));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::setCacheDirectory
     */
    public function testSetCacheDirectory_onExistingDir()
    {
        $this->assertTrue($this->cache->setCacheDirectory(self::EXISTINGDIR));
    }

    /**
     * @covers Gregwar\Cache\Cache::getCacheDirectory
     */
    public function testGetCacheDirectory_onDefault() 
    {
        $this->assertEquals( self::CACHEDIR , $this->cache->getCacheDirectory());
    }
    
    /**
     * @covers Gregwar\Cache\Cache::getCacheDirectory
     */
    public function testGetCacheDirectory_onChangedExisting() 
    {
        $this->cache->setCacheDirectory(self::EXISTINGDIR);
        $this->assertEquals(self::EXISTINGDIR, $this->cache->getCacheDirectory());
    }
    
    /**
     * @covers Gregwar\Cache\Cache::getCacheDirectory
     * dir must not be changed, equals to default
     */
    public function testGetCacheDirectory_onChangedNonExisting() 
    {
        $this->cache->setCacheDirectory(self::NONEXISTINGDIR);
        $this->assertEquals(self::CACHEDIR, $this->cache->getCacheDirectory());
    }
    
    /**
     * @covers Gregwar\Cache\Cache::set
     * @covers Gregwar\Cache\Cache::createCacheDir
     */
    public function testSet_onWritableCacheDir() 
    {
        $this->assertTrue($this->cache->set('mycacheid','thecontent'));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::set
     * @covers Gregwar\Cache\Cache::createCacheDir
     * @expectedException Exception
     * Must throw on exception if chars others than alpha and digits
     */
    public function testSet_onCacheIdWithSpecialChars() 
    {
        $this->cache->set('?././*y..ac?eid','thecontent!');
    }
    
    /**
     * @covers Gregwar\Cache\Cache::set
     * @covers Gregwar\Cache\Cache::createCacheDir
     */
    public function testSet_onNotWritableCacheDir() 
    {
        $this->cache->setCacheDirectory(self::NONWRITABLEDIR);
        $this->assertFalse($this->cache->set('mycacheid','thecontent'));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::exists
     */
    public function testExits_onNoCondition()
    {
        $this->cache->set('testing', 'testExits_onNoCondition');
        $this->assertTrue($this->cache->exists('testing'));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::exists
     * Data should be cached
     */
    public function testExits_onMaxAgeValid()
    {
        $conditions = array('max-age' => 60); // 60 seconds
        
        $this->cache->set('testing', 'testExits_onMaxAgeValid');
        $this->assertTrue($this->cache->exists('testing', $conditions));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::exists
     * Cache expired
     */
    public function testExits_onMaxAgeExpired()
    {
        $conditions = array('max-age' => 1); // 1 second
        
        $this->cache->set('testing', 'testExits_onMaxAgeExpired');
        sleep(2);
        $this->assertFalse($this->cache->exists('testing', $conditions));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::exists
     * Cache expired - -1 second
     */
    public function testExits_onMaxAgeAlwaysExpired()
    {
        $conditions = array('max-age' => -1);
        
        $this->cache->set('testing', 'testExits_onMaxAgeAlwaysExpired');
        $this->assertFalse($this->cache->exists('testing', $conditions));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::exists
     * Cache expired - 0 second
     * 0 second proprably means 'no cache'
     */
    public function testExits_onMaxAgeZero()
    {
        $conditions = array('max-age' => 0);
        
        $this->cache->set('testing', 'testExits_onMaxAgeZero');
        $this->assertFalse($this->cache->exists('testing', $conditions));
    }
   
    /**
     * @covers Gregwar\Cache\Cache::get
     * @covers Gregwar\Cache\Cache::exists
     * @covers Gregwar\Cache\Cache::checkConditions
     */
    public function testGet_onDefined() 
    {
        $this->cache->set('testing', 'testGet_onDefined');
        $this->assertEquals('testGet_onDefined', $this->cache->get('testing'));
    }
    
   /**
     * @covers Gregwar\Cache\Cache::get
     * @covers Gregwar\Cache\Cache::exists
     * @covers Gregwar\Cache\Cache::checkConditions
     */
    public function testGet_onUndefined() 
    {
        $this->assertNull( $this->cache->get('undefined'));
    }
    
    /**
     * @covers Gregwar\Cache\Cache::get
     * @covers Gregwar\Cache\Cache::exists
     * @covers Gregwar\Cache\Cache::checkConditions
     * Condition make the cache expired, must return NULL
     */
    public function testGet_onDefined_withConditions() 
    {
        $this->cache->set('testing', 'testGet_onDefined_withConditions');
        $conditions = array('max-age' => 0);
        $this->assertNull( $this->cache->get('testing', $conditions) );
    }
    
    /**
     * Check if configuration passed on constuctor is respected : condition max-age
     * @covers Gregwar\Cache\Cache::__construct()
     * because of max-age:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_condition()
    {
        // initial_conditions : no cache
        $initial_conditions = array('conditions' => array( 'max-age' => 0) );
        $cache = new Cache($initial_conditions);
        $cache->set('testing', 'onConfigPassed_condition');
        $this->assertFalse($cache->exists('testing'));
    }
    
    /**
     * Check if configuration passed on constuctor is respected : cachedir
     * @covers Gregwar\Cache\Cache::__construct()
     * because of max-age:0 passed in constructor, cache must be considered expired
     */
    public function testConstuctor_onConfigPassed_cachedir()
    {
        // change cache dir
        $initial_conditions = array('cacheDirectory' => self::EXISTINGDIR );
        $cache = new Cache($initial_conditions);
        $this->assertEquals(self::EXISTINGDIR, $cache->getCacheDirectory());
    }
    /**
     * executed after each test to clear environnement
     */
    protected function tearDown()
    {
        // remove cache dir created in test
        $cd = $this->cache->getCacheDirectory('acache');
        `chmod +w $cd -R`;
        `rm -Rf $cd`;
    }

}
