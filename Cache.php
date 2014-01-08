<?php

namespace Gregwar\Cache;

/**
 * A cache system based on files
 *
 * @todo validate cache_id with regexp
 * 
 * @author Sébastien Monterisi <SebSept@github>
 * @author Gregwar <g.passault@gmail.com>
 */
class Cache
{
    /**
     * Cache directory
     */
    protected $cacheDirectory;

    /**
     * directories size max depth
     *
     * For instance, if the file is helloworld.txt and the depth size is
     * 5, the cache file will be: h/e/l/l/o/helloworld.txt
     *
     * This is useful to avoid reaching a too large number of files into the 
     * cache system directories
     * @var int $pathDepth
     */
    protected $pathDepth = 5;

    /**
     * default configuration options
     * @var array 
     */
    protected $options = ['cacheDirectory' => 'cache', 
                          'conditions' => ['max-age' => 86400]
            ];
    
    /**
     * cache conditions
     * 
     * keys can be only 'max-age'
     * will be overrided in __construct with $options['conditions']
     * @var array associative array
     */
    protected $conditions = [];
    
    /**
     * Constructs the cache system
     * 
     * Options param can be 'cacheDirectory' and 'conditions' @see Gregwar\Cache\Cache::$conditions
     * @param array $options 
     */
    public function __construct($options = array())
    {
        // merge default options with passed
        $this->options = array_merge($this->options, $options);

	$this->cacheDirectory = $this->options['cacheDirectory'];
        $this->conditions = $this->options['conditions'];
    }

    /**
     * Sets the cache directory
     * 
     * Set the cache directory if exists
     *
     * @todo also check that dir is writable 
     * 
     * @param string $cacheDirectory the cache directory. Without ending '/'
     * @return bool
     */
    public function setCacheDirectory($cacheDirectory)
    {
        if(file_exists($cacheDirectory))
        {
            $this->cacheDirectory = $cacheDirectory;
            return true;
        }
	return false;
    }

    /**
     * Gets the cache directory
     *
     * @return string the cache directory
     */
    public function getCacheDirectory()
    {
	return $this->cacheDirectory;
    }

    /**
     * Set directories Path max depth
     *
     * @todo add min and max constants to validate size value
     * @param int $size path max depth
     * @return $this
     */
    public function setPathDepth($size)
    {
        if(filter_var($size, FILTER_VALIDATE_INT) && $size > 0)
                $this->pathDepth = $size;

        return $this;
    }


    /**
     * Gets the cache file path
     *
     * @todo refactor/recode
     * @param string $cacheId cache file name
     */
    public function getCachePath($cacheId)
    {
	$path = array();

	// Getting the length of the filename before the extension
	$parts = explode('.', $cacheId);
	$len = strlen($parts[0]);

	for ($i=0; $i<min($len, $this->pathDepth); $i++) {
	    $path[] = $cacheId[$i];

        }

	$path = implode('/', $path);

	$path .= '/' . $cacheId;
        return $this->getCacheDirectory() . '/' . $path;
    }
    
    
    /**
     * Create the directories where to create $cachePath
     * 
     * @param string $cachePath directory where to put file 
     * @return bool
     */
    protected function createCacheDir($cachePath)
    {
        $is_dir = is_dir($cachePath);
        $mkdir = @mkdir( $cachePath, 0775, true );
        if(isset($_ENV['debug']) && $_ENV['debug'] && !$is_dir && !$mkdir )
            throw new \Exception('Failled to create dir '.$cachePath);
        
        return $is_dir || $mkdir;
    }

    /**
     * Checks that the cache conditions are respected
     *
     * @param string $cacheFile the cache file to check
     * @param array $conditions an array of conditions to check, overrides current conditions
     * @return bool
     */
    protected function checkConditions($cacheFile, array $conditions = array())
    {
        // Implicit condition: the cache file should exist
        if (!file_exists($cacheFile)) {
	    return false;
	}

        // merge passed $conditions with currents
        $conditions = array_merge($this->conditions, $conditions);
        
	foreach ($conditions as $type => $value) {
	    switch ($type) {
            case 'max-age':
		// Return false if the file is older than $value
                $age = time() - filectime($cacheFile);
                if ($age >= $value) {
                    return false;
                }
		break;
	    default:
		throw new \Exception('Cache condition '.$type.' not supported');
	    }
	}

	return true;
    }

    /**
     * Checks if the targt filename exists in the cache and if the conditions
     * are respected
     *
     * @param $filename the filename 
     * @param $conditions the conditions to respect
     */
    public function exists($filename, array $conditions = array())
    {
        $cacheFile = $this->getCachePath($filename, true);

	return $this->checkConditions($cacheFile, $conditions);
    }

    /**
     * Caches contents
     * 
     * @todo throw Exception or display error if in debug mode (?)
     * 
     * @param string $cacheId 
     * @param string $contents contents to cache
     * @return bool
     */
    public function set($cacheId, $contents)
    {
	$cachePath = $this->getCachePath($cacheId);
        $create_dir = $this->createCacheDir( dirname($cachePath) );
        $create_file = ( @file_put_contents($cachePath, $contents) !== false );
        if(isset($_ENV['debug']) && $_ENV['debug'] && !$create_file)
            throw new \Exception('Failled to create file '.$cachePath);
            
        return $create_dir && $create_file;
    }

    /**
     * Get data from the cache
     * 
     * @param string $cacheId
     * @param array $conditions Additionnal conditions, overrides defaults @see Gregwar\Cache\Cache::$conditions
     */
    public function get($cacheId, array $conditions = array())
    {
        // merge passed $conditions with currents
        $conditions = array_merge($this->conditions, $conditions);
        
	if ($this->exists($cacheId, $conditions)) 
        {
	    return file_get_contents($this->getCachePath($cacheId, true));
	} 
        else 
        {
	    return NULL;
	}
    }

    /**
     * Is this URL remote?
     */
    protected function isRemote($file)
    {
        return preg_match('/^http(s{0,1}):\/\//', $file);
    }

    /**
     * Get or create the cache entry
     *
     * @param $filename the cache file name
     * @param $conditions an array of conditions about expiration
     * @param $function the closure to call if the file does not exists
     * @param $file returns the cache file or the file contents
     * @param $actual returns the actual cache file
     */
    public function getOrCreate($filename, array $conditions = array(), \Closure $function, $file = false, $actual = false)
    {
        $cacheFile = $this->getCachePath($filename, true, true);
        $data = null;

        if ($this->check($filename, $conditions)) {
            $data = file_get_contents($cacheFile);
        } else {
            @unlink($cacheFile);
            $data = $function($cacheFile);

            // Test if the closure wrote the file or if it returned the data
            if (!file_exists($cacheFile)) {
                $this->set($filename, $data);
            } else {
                $data = file_get_contents($cacheFile);
            }
        }

        return $file ? $this->getCachePath($filename, $actual) : $data;
    }

    /**
     * Alias to getOrCreate with $file = true
     */
    public function getOrCreateFile($filename, array $conditions = array(), \Closure $function, $actual = false)
    {
        return $this->getOrCreate($filename, $conditions, $function, true, $actual);
    }
}
