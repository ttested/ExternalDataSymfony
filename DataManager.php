<?php
 
namespace src\Decorator;
 
use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider; 
 
class DataManager extends DataProvider
{
    private $cache;
    private $logger;
 
    /**
	* @param string $host
	* @param string $user
	* @param string $password
	*/
    public function __construct($host, $user, $password)
    {
        parent::__construct($host, $user, $password);
    }
	
	public function setCache(CacheItemPoolInterface $cache)
	{
		$this->cache = $cache;
	}
 
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
 
    /**
	* {@inheritdoc}
	*/
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
 
            $result = parent::get($input);
 
            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+86400 seconds') 
                );
 
            return $result;
        } catch (Throwable $t) {
            $errstr = "Error: {$t->getMessage()}\n{$t->getTraceAsString()}";
            $this->logger->critical($errstr);
        }
 
        return [];
    }
 
    public function getCacheKey(array $input)
    {
        return md5($input);
    }
}