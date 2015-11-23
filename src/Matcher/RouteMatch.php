<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class RouteMatch 
{
    /**
     * @var array 
     */
    protected $params = [];

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @param string $routeName
     * @param array $params
     */
    public function __construct($routeName, array $params = [])
    {
        $this->routeName = $routeName;
        $this->replace($params);
    }

    /**
     * @return string
     */
    public function getRouteName() 
    {
        return $this->routeName;
    }
    
    /**
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) 
        {
            return $this->params[$key];
        }

        return is_callable($default) ? call_user_func($default) : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        switch ($key) 
        {
            case Route::ATTRIBUTES:
                $params = explode('/', trim($value, '/'));
                $n = count($params);

                if ($n > 0) 
                {
                    if ($n % 2 == 1) 
                    {
                        $params[] = '';
                        $n++;
                    }

                    for ($i = 0; $i < $n; ++$i)
                    {
                        if (preg_match('/^[a-z0-9-_]+$/i', $params[$i])) 
                        {
                            if (!$this->has($params[$i]))
                            {
                                $this->set($params[$i], rawurldecode($params[++$i]));
                            }
                        }
                    }
                }
                break;
            default:
                $this->params[$key] = trim($value, '/');
                break;
        }
    }
    
    /**
     * @return array
     */
    public function all()
    {
        return $this->params;
    }
    
    /**
     * @param array $data
     */
    public function replace(array $data)
    {
        $this->params = [];

        foreach ($data as $key => $value)
        {
            $this->set($key, $value);
        }
    }
    
    /**
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->params[$key]);
    }
}
