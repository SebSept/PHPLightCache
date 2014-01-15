<?php
/**
 * SimpleFileCache
 * Lightweight file cache provider
 *
 * @author  Sébastien Monterisi (main author) <sebastienmonterisi@yahoo.fr>
 * @link    https://github.com/SebSept/SimpleFileCache
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace SebSept\SimpleFileCache;

class Cache
{
    /**
     * Cache directory
     * @var string where is the cache dir - absolute path - must exist
     */
    protected $directoryPath;

    /**
     * directories max depth
     *
     * set in  __construct with $options['pathDepth']
     * For instance, if the file is helloworld.txt and the depth size is
     * 5, the cache file will be: h/e/l/l/o/helloworld.txt
     *
     * @var int $pathDepth directories max depth
     */
    protected $pathDepth;

    /**
     * default configuration options
     * @var array
     */
    protected $options = ['directoryPath' => 'cache',
                          'delay' => 86400,
                          'pathDepth' => 5];

    /**
     * cache delay
     *
     * set in __construct with $options['delay']
     * @var int delay in seconds, time to live, time to consider cache valid. Defaults to 86400 = 60*60*24 : 24 hours
     */
    protected $delay;

    /**
     * @var string regular expression to validate cache ids
     */
    const CACHEID_VALIDATION_REGEXP = '|^[\w\d]{1,255}$|';

    /**
     * @var int directory path minimal depth
     */
    const DEPTH_MIN = 0;

     /**
     * @var int directory path minimal depth
     */
    const DEPTH_MAX = 12;

    /**
     * Constructs the cache system
     *
     * Options param can be 'directoryPath' (string)
     * and 'delay' {@see SebSept\SimpleFileCache\Cache::$delay}
     *
     * @todo better doc
     * @param array $options 'directoryPath', 'delay'
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);

        $this->setDirectoryPath($this->options['directoryPath']);
        $this->delay = (int) $this->options['delay'];
        $this->setPathDepth($this->options['pathDepth']);
    }

    /**
     * Set the cache directory (if exists)
     *
     * @throws Exception Cache directory not existing or not writable
     * @param  string    $directoryPath the cache directory. Without ending '/'
     * @return bool
     */
    public function setDirectoryPath($directoryPath)
    {
        if (!file_exists($directoryPath)) {
            throw new \Exception('Cache directory "'.$directoryPath.'" doesn\'t exists');
        }
        if (!is_writable($directoryPath)) {
            throw new \Exception('Cache directory "'.$directoryPath.'" not writable');
        }
        $this->directoryPath = $directoryPath;

        return true;
    }

    /**
     * Get the cache directory
     *
     * @return string the cache directory
     */
    public function getDirectoryPath()
    {
        return $this->directoryPath;
    }

    /**
     * Set directories Path max depth
     *
     * @param  int  $depth path max depth
     * @return bool
     */
    public function setPathDepth($depth)
    {
        if (filter_var($depth, FILTER_VALIDATE_INT)
                && $depth >= self::DEPTH_MIN
                && $depth <= self::DEPTH_MAX) {
            $this->pathDepth = $depth;

            return true;
        }

        return false;
    }

    /**
     * Get the cache file path
     *
     * @param  string $cacheId cache file name
     * @return string path to cache file
     */
    public function getFilePath($cacheId)
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

        return $this->getDirectoryPath() . '/' . $path;
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
     * Cache is expired ?
     *
     * @param  string $cacheFile the cache file to check
     * @param  int    $delay,    overrides current delay if set
     * @return bool
     */
    protected function isExpired($cacheFile, $delay = null)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        $delay = is_null($delay) ? $this->delay : (int) $delay;
        $age = time() - filectime($cacheFile);
        if ($age >= $delay) {
            return false;
        }

        return true;
    }

    /**
     * Checks if cacheID exists with param delay or default delay
     *
     * @param  string         $cacheId
     * @param  mixed null|int $delay
     * @return bool
     */
    public function exists($cacheId, $delay = null)
    {
        $delay = is_null($delay) ? $this->delay : (int) $delay;
        $cacheFile = $this->getFilePath($cacheId);

        return $this->isExpired($cacheFile, $delay);
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
        $cachePath = $this->getFilePath($cacheId);
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
     * @param  int    $delay   overrides defaults {@see SebSept\SimpleFileCache\Cache::$delay}
     * @return mixed  string|null null if cache doesn't exists in this conditions, string if exists
     */
    public function get($cacheId, $delay = null)
    {
        if (!$this->checkValidCacheId($cacheId)) {
            return null;
        }
        $delay = is_null($delay) ? $this->delay : (int) $delay;
        if ($this->exists($cacheId, $delay)) {
            return file_get_contents($this->getFilePath($cacheId));
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
        $filePath = $this->getFilePath($cacheId);
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
     * SetDelay
     *
     * Set expiration delay.
     * Return true if param value is an int or string castable to int
     *
     * @param  int  $delay
     * @return bool
     */
    public function setDelay($delay)
    {
        $this->delay = (int) $delay;

        return ( (string) $this->delay === (string) $delay);
    }

    /**
     * Flush all caches
     *
     * Remove than recreate the cache directory
     *
     * @return bool
     * @throws \Exception on failed to remove cache directory
     */
    public function flush()
    {
        $cacheDir = $this->getDirectoryPath();
        $delete = `rm -Rf $cacheDir 2>&1`;
        if(!is_null($delete))
            throw new \Exception ("Failed to flush cache dir '$cacheDir' : '$delete'");

        return $this->createCacheDir($cacheDir);
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
