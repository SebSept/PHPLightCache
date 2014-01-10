<?php
/**
 * PHPLightCacheFS
 * Lightweight file cache provider
 *
 * @author  Sébastien Monterisi (main author) <sebastienmonterisi@yahoo.fr>
 * @author  Gregwar <g.passault@gmail.com>
 * @link    https://github.com/SebSept/PHPLightCacheFS
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace SebSept\Cache;

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
                          'conditions' => ['max-age' => 86400] ];

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
    const CACHEID_VALIDATION_REGEXP = '|^[\w\d]{1,255}$|';

    /**
     * Constructs the cache system
     *
     * Options param can be 'cacheDirectory' (string)
     * and 'conditions' {@see SebSept\Cache\Cache::$conditions}
     *
     * @todo better doc
     * @param array $options 'cacheDirectory', 'conditions'
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->setCacheDirectory($this->options['cacheDirectory']);
        $this->conditions = $this->options['conditions'];
    }

    /**
     * Sets the cache directory (if exists)
     *
     * @todo also check that dir is writable (?)
     * @param  string $cacheDirectory the cache directory. Without ending '/'
     * @return bool
     */
    public function setCacheDirectory($cacheDirectory)
    {
        if (file_exists($cacheDirectory)) {
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
     * @param  int $depth path max depth
     * @return bool
     */
    public function setPathDepth($depth)
    {
        if (filter_var($depth, FILTER_VALIDATE_INT) && $depth > 0) {
            $this->pathDepth = $depth;
            return true;
        }
        return false;
    }

    /**
     * Gets the cache file path
     *
     * @param  string $cacheId cache file name
     * @return string path to cache file
     */
    public function getCachePath($cacheId)
    {
        $this->checkValidCacheId($cacheId);
        $path = array();

        for ($i=0; $i<min(strlen($cacheId), $this->pathDepth); $i++) {
            $path[] = $cacheId[$i];
        }
        $path = implode('/', $path);
        $path .= '/' . $cacheId;
        if (isset($_ENV['debug']) && $_ENV['debug']) {
            trigger_error('Path : '.$path);
        }

        return $this->getCacheDirectory() . '/' . $path;
    }

    /**
     * Create the directories where to create $cachePath
     *
     * @param  string $cachePath directory where to put file
     * @return bool
     */
    protected function createCacheDir($cachePath)
    {
        $isDir = is_dir($cachePath);
        $mkdir = @mkdir($cachePath, 0775, true);
        if (isset($_ENV['debug']) && $_ENV['debug'] && !$isDir && !$mkdir) {
            throw new \Exception('Failed to create dir '.$cachePath);
        }
        return $isDir || $mkdir;
    }

    /**
     * Checks that the cache conditions are respected
     *
     * @param  string $cacheFile  the cache file to check
     * @param  array  $conditions an array of conditions to check, overrides current conditions
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
                    $age = time() - filectime($cacheFile);
                    if ($age >= $value) {
                        return false;
                    }
                    break;
                default:
                    throw new \Exception('Cache condition "'.$type.'" not supported');
            }
        }
        return true;
    }

    /**
     * Checks if cacheID exists in conditions param
     *
     * @param  string $cacheId
     * @param  array  $conditions
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
     * @throws Exception if Failed to create file
     * @param  string    $cacheId
     * @param  string    $contents contents to cache
     * @return bool
     */
    public function set($cacheId, $contents)
    {
        $this->checkValidCacheId($cacheId);
        $cachePath = $this->getCachePath($cacheId);
        $createDir = $this->createCacheDir(dirname($cachePath));
        $createFile = ( @file_put_contents($cachePath, $contents) !== false );
        if (isset($_ENV['debug']) && $_ENV['debug'] && !$createFile) {
            throw new \Exception('Failed to create file '.$cachePath);
        }
        return $createDir && $createFile;
    }

    /**
     * Get data from the cache
     *
     * @param  string $cacheId
     * @param  array  $conditions Additionnal conditions, overrides defaults {@see SebSept\Cache\Cache::$conditions}
     * @return mixed  string|null null if cache doesn't exists in this conditions, string if exists
     */
    public function get($cacheId, array $conditions = [])
    {
        if (!$this->checkValidCacheId($cacheId)) {
            return null;
        }
        $conditions = array_merge($this->conditions, $conditions);
        if ($this->exists($cacheId, $conditions)) {
            return file_get_contents($this->getCachePath($cacheId));
        }
        return null;
    }

    /**
     * Deletes cache
     *
     * @throws Exception if failed to delete cache file
     * @param  type      $cacheID
     * @return bool
     */
    public function delete($cacheId)
    {
        $filePath = $this->getCachePath($cacheId);
        // file doesn't exists : return true
        if (!file_exists($filePath)) {
            return true;
        }
        if (!@unlink($filePath)) {
            throw new \Exception('Failed to delete existing file '.$filePath);
        }
        return true;
    }

    /**
     * check the $cacheId is valid
     *
     * @codeCoverageIgnore
     * @return bool
     */
    private function checkValidCacheId($cacheId)
    {
        $match = preg_match(self::CACHEID_VALIDATION_REGEXP, $cacheId);
        if (!$match) {
            throw new \Exception('Invalid cache id : must match the regexp '.self::CACHEID_VALIDATION_REGEXP);
        }
        return $match;
    }
}
