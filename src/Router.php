<?php

namespace Elixir\Routing;

use Elixir\Config\Config;
use Elixir\Config\Writer\WriterInterface;
use Elixir\Routing\Collection;
use Elixir\Routing\Generator\GeneratorInterface;
use Elixir\Routing\Matcher\MatcherInterface;
use Elixir\Routing\Parser;
use Elixir\Routing\Route;
use Elixir\Routing\RouterInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Router implements RouterInterface 
{
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
     * @var Config; 
     */
    protected $config;
    
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
        list($name, $parameters, $options, $priority) = Parser::parseRoute($pattern, $config);
        
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
     * @param Config|array $config
     */
    public function load($config)
    {
        if (is_array($config))
        {
            $this->addCollection(Parser::parse($config));
        }
        else
        {
            if (!$config instanceof Config)
            {
                $this->config = $this->config ?: new Config();
                $this->config->replace([]);
                $this->config->load($config);
            }
            else
            {
                $this->config = $config;
            }
            
            $this->addCollection(Parser::parse($this->config->all()));
        }
    }
    
    /**
     * @param WriterInterface $writer
     * @param string $file
     * @return boolean
     */
    public function export(WriterInterface $writer, $file)
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
        
        return $writer->export($data, $file);
    }
    
    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function match($path)
    {
        if(null === $this->matcher)
        {
            throw new RuntimeException('Matcher implementation is not defined.');
        }
        
        $pathInfos = trim($path, '/');
        return $this->matcher->match($this->collection, $pathInfos);
    }
    
    /**
     * {@inheritdoc}
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function generate($name, array $options = [], $mode = GeneratorInterface::URL_RELATIVE)
    {
        if(null === $this->generator)
        {
            throw new RuntimeException('Generator implementation is not defined.');
        }
        
        $route = $this->collection->get($name);
        
        if(null === $route)
        {
            throw new InvalidArgumentException(sprintf('Route "%s" does not exist.', $name));
        }
        
        return $this->generator->generate($route, $options, $mode);
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
