<?php

namespace Elixir\Routing;

use Elixir\Config\Cache\CacheableInterface;
use Elixir\Config\Loader\LoaderFactory;
use Elixir\Config\Loader\LoaderFactoryAwareTrait;
use Elixir\Config\Writer\WriterInterface;
use Elixir\Routing\Collection;
use Elixir\Routing\Generator\GeneratorInterface;
use Elixir\Routing\LoadParser;
use Elixir\Routing\Matcher\MatcherInterface;
use Elixir\Routing\Route;
use Elixir\Routing\RouterInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Router implements RouterInterface, CacheableInterface
{
    use LoaderFactoryAwareTrait;
    
    /**
     * @var CacheableInterface 
     */
    protected $cache;
    
    /**
     * @var MatcherInterface 
     */
    protected $matcher;
    
    /**
     * @var GeneratorInterface 
     */
    protected $generator;
    
    /**
     * @var Collection
     */
    protected $collection;
    
    /**
     * @param MatcherInterface $matcher
     * @param GeneratorInterface $generator
     */
    public function __construct(MatcherInterface $matcher = null, GeneratorInterface $generator = null)
    {
        $this->collection = new Collection();
        $this->matcher = $matcher;
        $this->generator = $generator;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcher()
    {
        return $this->matcher;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGenerator()
    {
        return $this->generator;
    }
    
    /**
     * @param CacheableInterface $value
     */
    public function setCacheStrategy(CacheableInterface $value)
    {
        $this->cache = $value;
    }
    
    /**
     * @return CacheableInterface
     */
    public function getCacheStrategy()
    {
        return $this->cache;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadCache()
    {
        if (null === $this->cache)
        {
            return false;
        }
        
        $data = $this->cache->loadCache();
        
        if ($data)
        {
            $data = LoadParser::parse($data);
            $this->addCollection($data);
        }
        
        return $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function cacheLoaded()
    {
        if (null === $this->cache)
        {
            return false;
        }
        
        return $this->cache->cacheLoaded();
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function get($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['GET', 'HEAD'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function post($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['POST'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function put($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['PUT'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function delete($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['DELETE'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function patch($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['PATCH'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function any($pattern, $config)
    {
        if (!is_array($config))
        {
            $config = [$config];
        }
        
        $config[Route::METHODS] = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PATCH'];
        $this->route($pattern, $config);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function route($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = LoadParser::parseRoute($pattern, $config);
        
        $this->addRoute(
            $name ?: md5($pattern . '_' . $priority), 
            new Route($pattern, $parameters, $options), 
            $priority
        );
    }

    /**
     * @param callable $callable
     */
    public function group(callable $callable)
    {
        $current = $this->collection;
        $this->collection = clone $this->collection;
        
        call_user_func_array($callback, [$this]);
        
        $current->merge($this->collection);
        $this->collection = $current;
    }
    
    /**
     * @param string $name
     * @param Route $route
     * @param integer $priority
     */
    public function addRoute($name, Route $route, $priority = 0)
    {
        $this->collection->add($name, $route, $priority);
    }
    
    /**
     * @param Collection $collection
     */
    public function addCollection(Collection $collection)
    {
        $this->collection->merge($collection);
    }
    
    /**
     * {@inheritdoc}
     */
    public function load($config, array $options = [])
    {
        if ($this->cacheLoaded() && $this->isFreshCache())
        {
            return;
        }
        
        if ($config instanceof self)
        {
            $this->addCollection($config->getCollection());
        } 
        else 
        {
            if (is_callable($config))
            {
                $data = call_user_func_array($config, [$this]);
            }
            else
            {
                if (null === $this->loaderFactory)
                {
                    $this->loaderFactory = new LoaderFactory();
                    LoaderFactory::addProvider($this->loaderFactory);
                }
                
                $loader = $this->loaderFactory->create($config, $options);
                $data = $loader->load($config);
            }
            
            $this->addCollection(LoadParser::parse($data));
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function export(WriterInterface $writer, $file)
    {
        return $writer->export($this->getExportableData(), $file);
    }
    
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function match($path)
    {
        if(null === $this->matcher)
        {
            throw new \RuntimeException('Matcher implementation is not defined.');
        }
        
        $path = trim($path, '/');
        return $this->matcher->match($this->collection, $path);
    }
    
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function generate($name, array $options = [], $mode = GeneratorInterface::URL_RELATIVE)
    {
        if(null === $this->generator)
        {
            throw new \RuntimeException('Generator implementation is not defined.');
        }
        
        $route = $this->collection->get($name);
        
        if(null === $route)
        {
            throw new \InvalidArgumentException(sprintf('Route "%s" does not exist.', $name));
        }
        
        return $this->generator->generate($route, $options, $mode);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isFreshCache()
    {
        if (null === $this->cache)
        {
            return false;
        }
        
        return $this->cache->isFreshCache();
    }
    
    /**
     * {@inheritdoc}
     */
    public function exportToCache(array $data = null)
    {
        if (null === $this->cache)
        {
            return false;
        }
        
        if ($data)
        {
            $this->addCollection(LoadParser::parse($data));
        }
        
        return $this->cache->exportToCache($this->getExportableData());
    }
    
    /**
     * {@inheritdoc}
     */
    public function invalidateCache()
    {
        if (null === $this->cache)
        {
            return false;
        }
        
        return $this->cache->invalidateCache();
    }
    
    /**
     * @return array
     */
    protected function getExportableData()
    {
        $data = [];

        foreach ($this->collection->all(true) as $name => $config)
        {
            $data[$name] = [
                'parameters' => $config['parameters'],
                'options' => $config['options'],
                'priority' => $config['priority']
            ];
        }
        
        return $data;
    }

    /**
     * @ignore
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->collection, $method], $arguments);
    }
    
    /**
     * @ignore
     */
    public function __debugInfo()
    {
        return $this->collection;
    }
}
