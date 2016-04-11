<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Collection;
use Elixir\Routing\Matcher\MatcherInterface;
use Elixir\Routing\Request;
use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class URLMatcher implements MatcherInterface
{
    /**
     * @var string
     */
    const REGEX_SEPARATOR = '`';
    
    /**
     * @var Request
     */
    protected $request;
    
    /**
     * @var array 
     */
    protected $references = [];
    
    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Collection $collection, $path = null)
    {
        $path = $path ?: $this->request->getPathInfo();
        
        if (!$collection->isSorted())
        {
            $collection->sort();
        }
        
        foreach ($collection as $name => $route)
        {
            // Host
            $host = $route->getOption(Route::HOST, null);
            
            if ($host && $host !== $this->request->getHost())
            {
                continue;
            }
            
            // Methods
            $methods = $route->getOption(Route::METHODS, []);
            
            if (count($methods) > 0)
            {
                $methods = array_map(function($el)
                {
                    return strtoupper($el);
                }, 
                $methods);
                
                if (!in_array($this->request->getMethod(), $methods))
                {
                    continue;
                }
            }
            
            // Secure or not
            if ($route->hasOption(Route::SECURE))
            {
                if ($route->getOption(Route::SECURE) !== $this->request->isSecure())
                {
                    continue;
                }
            }
            
            // Create pattern
            $pattern = $this->compile($route);
            
            // Check eligibility
            if (preg_match($pattern, $path, $matches))
            {
                $routeMatch = $this->createRouteMatch($name, $route, $matches);
                
                // Assertion
                if ($route->hasOption(Route::ASSERT))
                {
                    $assert = $route->getOption(Route::ASSERT);
                    
                    if(false === call_user_func_array($assert, [$this->request, $routeMatch]))
                    {
                        continue;
                    }
                }
                
                // Filterize
                if ($route->hasOption(Route::MATCHED_FILTER))
                {
                    $filter = $route->getOption(Route::MATCHED_FILTER);
                    call_user_func_array($filter, [$this->request, $routeMatch]);
                }
                
                return $routeMatch;
            }
        }
        
        return null;
    }
    
    /**
     * @param Route $route
     * @return string
     */
    protected function compile(Route $route)
    {
        $pattern = $route->getPattern();
        
        foreach ($route->getOptions() as $key => $option)
        {
            switch ($key)
            {
                case Route::MIDDLEWARES:
                case Route::CONVERTERS:
                case Route::HOST:
                case Route::SECURE:
                case Route::METHODS:
                case Route::ASSERT:
                case Route::MATCHED_FILTER:
                case Route::GENERATE_FILTER:
                case Route::PREFIX:
                case Route::SUFFIX:
                    continue 2;
                    break;
                case Route::REPLACEMENTS:
                    foreach ($option as $k => $v)
                    {
                        $pattern = str_replace('%' . $k . '%', $v, $pattern);
                    }
                    
                    continue 2;
                    break;
                case Route::ATTRIBUTES:
                    $mask = '{' . $key . '}';
                    
                    if (false === strpos($pattern, $mask))
                    {
                        $pattern .= '{' . $key . '}';
                    }
                    break;
            }
            
            $pattern = str_replace('{' . $key . '}', '(?P<' . $this->protect($key) . '>' . $option . ')', $pattern);
        }
        
        return self::REGEX_SEPARATOR . '^' . $pattern . '$' . self::REGEX_SEPARATOR;
    }
    
    /**
     * @param string $str
     * @return string
     */
    protected function protect($str)
    {
        $key = str_replace(str_split('.\+*?[^]$(){}=!<>|:-%'), '', $str);
        $this->references[$key] = $str;
        
        return $key;
    }
    
    /**
     * @param string $name
     * @param Route $route
     * @param array $matches
     * @return RouteMatch
     */
    protected function createRouteMatch($name, Route $route, array $matches)
    {
        $match = new RouteMatch($name, $route->getParameters());
        $converters = $route->getConverters();
        
        foreach ($matches as $key => $value)
        {
            if (isset($this->references[$key]) && !empty($value))
            {
                $key = $this->references[$key];
                
                if (isset($converters[$key]))
                {
                    $value = call_user_func_array($converters[$key], [$value]);
                }
                
                $match->set($key, $value);
            }
        }
        
        $middlewares = $route->getMiddlewares();
        
        if (count($route->getMiddlewares()) > 0)
        {
            $match->set(Route::MIDDLEWARES, $middlewares);
        }
        
        return $match;
    }
}
