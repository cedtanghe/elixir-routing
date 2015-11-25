<?php

namespace Elixir\Routing;

use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Collection implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var integer
     */
    protected $serial = 0;

    /**
     * @var boolean
     */
    protected $sorted = false;
    
    /**
     * @var array
     */
    protected $decorators = [];
    
    /**
     * @var boolean
     */
    protected $decorateForever = false;

    /**
     * @param string $name
     * @return boolean
     */
    public function has($name) 
    {
        return isset($this->routes[$name]);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null) 
    {
        if ($this->has($name)) 
        {
            return $this->routes[$name]['route'];
        }

        return is_callable($default) ? call_user_func($default) : $default;
    }

    /**
     * @param string $name
     * @param Route $route
     * @param integer $priority
     */
    public function add($name, Route $route, $priority = 0) 
    {
        $this->sorted = false;
        $this->routes[$name] = [
            'route' => $this->decore($route),
            'priority' => $priority,
            'serial' => $this->serial++
        ];
    }

    /**
     * @param string $name
     */
    public function remove($name) 
    {
        unset($this->routes[$name]);
    }

    /**
     * @param boolean $withInfos
     * @return array
     */
    public function all($withInfos = false)
    {
        $routes = [];

        foreach ($this->routes as $key => $value)
        {
            $routes[$key] = $withInfos ? $value : $value['route'];
        }

        return $routes;
    }

    /**
     * @param array $routes
     */
    public function replace(array $routes)
    {
        $this->routes = [];
        $this->serial = 0;

        foreach ($routes as $name => $route)
        {
            $priority = 0;

            if (is_array($route))
            {
                $route = $route['route'];

                if (isset($route['priority'])) 
                {
                    $priority = $route['priority'];
                }
            }

            $this->add($name, $route, $priority);
        }
    }
    
    /**
     * @return void
     */
    public function sort() 
    {
        if (!$this->sorted)
        {
            uasort($this->routes, function (array $p1, array $p2)
            {
                if ($p1['priority'] === $p2['priority']) 
                {
                    return ($p1['serial'] < $p2['serial']) ? -1 : 1;
                }

                return ($p1['priority'] > $p2['priority']) ? -1 : 1;
            });
        
            $this->sorted = true;
        }
    }
    
    /**
     * @return boolean
     */
    public function isSorted()
    {
        return $this->sorted;
    }
    
    /**
     * @ignore
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * @ignore
     */
    public function offsetSet($key, $value) 
    {
        if (null === $key)
        {
            throw new \InvalidArgumentException('The key can not be undefined.');
        }

        $this->add($key, $value);
    }

    /**
     * @ignore
     */
    public function offsetGet($key) 
    {
        return $this->get($key);
    }

    /**
     * @ignore
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * @ignore
     */
    public function rewind() 
    {
        return reset($this->routes);
    }

    /**
     * @ignore
     */
    public function current() 
    {
        return $this->get($this->key());
    }

    /**
     * @ignore
     */
    public function key() 
    {
        return key($this->routes);
    }

    /**
     * @ignore
     */
    public function next()
    {
        return next($this->routes);
    }
    
    /**
     * @ignore
     */
    public function valid() 
    {
        return null !== $this->key();
    }

    /**
     * @ignore
     */
    public function count()
    {
        return count($this->routes);
    }
    
    /**
     * @param Collection|array $collection
     */
    public function merge($collection)
    {
        if ($collection instanceof self) 
        {
            $collection = $collection->all(true);
        }

        if (count($collection) > 0) 
        {
            $this->sorted = false;

            foreach ($collection as $name => $config)
            {
                $priority = 0;
                $serial = 0;

                if (is_array($config))
                {
                    $route = $config['route'];

                    if (isset($config['priority']))
                    {
                        $priority = $config['priority'];
                    }

                    if (isset($config['serial'])) 
                    {
                        $serial = $config['serial'];
                    }
                }
                else
                {
                    $route = $config;
                }

                $this->routes[$name] = [
                    'route' => $this->decore($route),
                    'priority' => $priority,
                    'serial' => ($this->_serial++) + $serial
                ];
            }
        }
    }
    
    /**
     * @param Route $route
     * @return Route
     */
    protected function decore(Route $route)
    {
        foreach ($this->decorators as $method => $arguments)
        {
            call_user_func_array([$route, $method], $arguments);
        }
        
        return $route;
    }
    
    /**
     * @return Collection
     */
    public function forever()
    {
        $this->decorateForever = true;
        return $this;
    }
    
    /**
     * @ignore
     */
    public function __call($method, $arguments)
    {
        foreach ($this->routes as $config)
        {
            call_user_func_array([$config['route'], $method], $arguments);
        }
        
        if ($this->decorateForever)
        {
            $this->decorators[][$method] = $arguments;
            $this->decorateForever = false;
        }
    }
    
    /**
     * @ignore
     */
    public function __clone() 
    {
        $this->routes = [];
        $this->decorateForever = false;
        $this->serial = 0;
        $this->sorted = false;
    }

    /**
     * @ignore
     */
    public function __debugInfo()
    {
        return $this->all(true);
    }
}
