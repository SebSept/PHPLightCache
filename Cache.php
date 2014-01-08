<?php

namespace SebSept\Cache;

/**
 * A cache system based on files
 *
 * 
 * @author Sébastien Monterisi <SebSept@github> - almost all code is now from this author
 * @author Gregwar <g.passault@gmail.com> 
 */
class Cache
{
    /**
     * Cache directory
     * @var string where is the cache dir - absolute path - must exist
     */
    protected $cacheDirectory;

    /**
     * directories max depth
     *
     * For instance, if the file is helloworld.txt and the depth size is
     * 5, the cache file will be: h/e/l/l/o/helloworld.txt
     *
     * @var int $pathDepth directories max depth
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
     * @var string regular expression to validate cache ids
     */
    const valid_cache_id_regexp = '|^[\w\d]{1,255}$|';
    
    /**
     * Constructs the cache system
     * 
     * Options param can be 'cacheDirectory' (string) and 'conditions' @see SebSept\Cache\Cache::$conditions
     * @todo better doc
     * @param array $options 
     */
    public function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);

	$this->setCacheDirectory( $this->options['cacheDirectory']);
        $this->conditions = $this->options['conditions'];
    }

    /**
     * Sets the cache directory (if exists)
     * 
     * @todo also check that dir is writable (?)
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
     * @todo add min and max constants to validate size value + return bool
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
     * @param string $cacheId cache file name
     * @return string path to cache file
     */
    public function getCachePath($cacheId)
    {
        $this->checkValidCacheId($cacheId);
	$path = array();

	for ($i=0; $i<min(strlen($cacheId), $this->pathDepth); $i++) 
        {
	    $path[] = $cacheId[$i];
        }
	$path = implode('/', $path);
	$path .= '/' . $cacheId;
         if(isset($_ENV['debug']) && $_ENV['debug'])
             trigger_error('Path : '.$path);
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
    protected function checkConditions($cacheFile, array $conditions = [])
    {
        if (!file_exists($cacheFile)) {
	    return false;
	}

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
     * Checks if cacheID exists in conditions param
     *
     * @param string $cacheId
     * @param array $conditions
     * @return bool
     */
    public function exists($cacheId, array $conditions = [])
    {
        $conditions = array_merge($this->conditions, $conditions);        
        $cacheFile = $this->getCachePath($cacheId);
	return $this->checkConditions($cacheFile, $conditions);
    }

    /**
     * Caches contents
     * 
     * @throws Exception if 
     * @param string $cacheId 
     * @param string $contents contents to cache
     * @return bool
     */
    public function set($cacheId, $contents)
    {
        $this->checkValidCacheId($cacheId);
         
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
     * @todo better throw exception if invalid cacheId ?
     * 
     * @param string $cacheId
     * @param array $conditions Additionnal conditions, overrides defaults @see SebSept\Cache\Cache::$conditions
     * @return mixed string|NULL NULL if cache doesn't exists in this conditions, string if exists
     */
    public function get($cacheId, array $conditions = [])
    {
        if(!$this->checkValidCacheId($cacheId, false))
                return NULL;
        
        $conditions = array_merge($this->conditions, $conditions);
        
	if ($this->exists($cacheId, $conditions)) 
        {
	    return file_get_contents($this->getCachePath($cacheId));
	} 
        else 
        {
	    return NULL;
	}
    }
    
    /**
     * Deletes cache
     * 
     * @throws Exception if failed to delete cache file
     * @param type $cacheID
     * @return bool
     */
    public function delete($cacheId)
    {
        $file_path = $this->getCachePath($cacheId);
        // file doesn't exists : return true
        if(!file_exists($file_path))
            return true;
        if(!@unlink($file_path))
            throw new \Exception('Failed to delete existing file '.$file_path);
        return true;
    }

    /**
     * check the $cacheId is valid
     * 
     * @codeCoverageIgnore
     * @throws Exception if param $throwException && param $cacheId doesn't match self::valid_cache_id_regexp
     * @return bool
     */
    private function checkValidCacheId($cacheId, $throwException = true)
    {
        $match = preg_match(self::valid_cache_id_regexp, $cacheId);
        if($throwException && !$match)
            throw new \Exception('Invalid cache id : must match the regexp '.self::valid_cache_id_regexp);
        return $match;
    }
           
}
