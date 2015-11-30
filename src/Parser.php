<?php

namespace Elixir\Routing;

use Elixir\Routing\Collection;
use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Parser 
{
    /**
     * @param array $data
     * @return Collection;
     */
    public static function parse(array $data) 
    {
        $collection = new Collection();

        foreach ($data as $name => $config) 
        {
            if ($name === Route::GLOBAL_CONFIG)
            {
                continue;
            }

            if (!isset($config[Route::PATTERN]))
            {
                throw new \InvalidArgumentException('Invalid configuration, pattern must be defined.');
            }
            
            $pattern = $config[Route::PATTERN];
            list(, $parameters, $options, $priority) = Parser::parseRoute($pattern, $config);
        
            $collection->add(
                $name, 
                new Route($regex, $parameters, $options), 
                $priority
            );
        }
        
        if (isset($data[Route::GLOBAL_CONFIG]))
        {
            $routes = $collection->all(false);
            
            foreach ($data[Route::GLOBAL_CONFIG] as $key => $value) 
            {
                array_map(function($route) use ($key, $value)
                {
                    if (Route::isValidOption($key, $route))
                    {
                        $route->setOption($key, $value);
                    }
                    else
                    {
                        $route->setParameter($key, $value);
                    }
                }, $routes);
            }
        }
        
        return $collection;
    }
    
    /**
     * @param string $pattern
     * @param array|callable $config
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function parseRoute($pattern, $config)
    {
        $name = null;
        $parameters = [];
        $options = [];
        $priority = 0;
        
        if (is_array($config))
        {
            foreach ($config as $key => $value)
            {
                if (is_int($key))
                {
                    $parameters[Route::CALLABLE] = $value;
                }
                else if ($key === Route::NAME || $key === Route::NAME_ALIAS)
                {
                    $name = $value;
                }
                else if ($key === Route::PRIORITY || $key === Route::PRIORITY_ALIAS)
                {
                    $priority = $value;
                }
                else if ($key === Route::PARAMETERS)
                {
                    $parameters = array_merge($parameters, $value);
                }
                else if ($key === Route::OPTIONS)
                {
                    $options = array_merge($options, $value);
                }
                else if (Route::isValidOption($key, $pattern))
                {
                    $options[$key] = $value;
                }
                else
                {
                    $parameters[$key] = $value;
                }
            }
        }
        else if (is_callable($config))
        {
            $parameters[Route::CONTROLLER] = $config;
        }
        else
        {
            throw new \InvalidArgumentException('Invalid configuration, must be an array or callable.');
        }
        
        return [
            'name' => $name,
            'parameters' => $parameters,
            'options' => $options,
            'priority' => $priority
        ];
    }
}
