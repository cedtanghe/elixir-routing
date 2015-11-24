<?php

namespace Elixir\Routing;

use Elixir\Routing\Route;
use Elixir\Routing\Collection;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
trait RouterUtilTrait
{
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function get($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $options[Route::METHODS] = ['GET', 'HEAD'];
        
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function post($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $options[Route::METHODS] = ['POST'];
        
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function put($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $options[Route::METHODS] = ['PUT'];
        
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function delete($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $options[Route::METHODS] = ['DELETE'];
        
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function patch($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $options[Route::METHODS] = ['PATCH'];
        
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     */
    public function any($pattern, $config)
    {
        list($name, $parameters, $options, $priority) = $this->parseConfig($config);
        $this->addRoute($name, new Route($pattern, $parameters, $options), $priority);
    }
    
    /**
     * @param callable $callable
     */
    public function group(callable $callable)
    {
        // Todo
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
     * @param array|callable $config
     * @return array
     */
    protected function parseConfig($config)
    {
        $name = null;
        $parameters = [];
        $options = [];
        $priority = 0;
        
        // Todo
        
        return [
            'name' => $name,
            'parameters' => $parameters,
            'options' => $options,
            'priority' => $priority
        ];
    }
    
    /**
     * @ignore
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->collection, $method], $arguments);
    }
}
