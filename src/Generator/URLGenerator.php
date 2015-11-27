<?php

namespace Elixir\Routing\Generator;

use Elixir\Routing\Generator\GeneratorInterface;
use Elixir\Routing\Request;
use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class URLGenerator implements GeneratorInterface 
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
     * @param Request $request
     * @param boolean $strict
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
    public function generate(Route $route, array $options = [], $mode = self::URL_RELATIVE)
    {
        if ($route->hasOption(Route::GENERATE_FILTER))
        {
            $filter = $route->getOption(Route::GENERATE_FILTER);
            $options = call_user_func_array($filter, [$route, $options]);
        }
        
        $parameters = [];
        $attributes = [];
        $authorizeAttributes = $route->hasOption(Route::ATTRIBUTES);
        $replacements = $route->getParameter(Route::REPLACEMENTS, []);
        $query = $route->getParameter(Route::QUERY, []);
        
        $parseCallable = function($option) use ($parameters)
        {
            $parts = explode('::', $option);

            if (count($parts) === 3)
            {
                $parameters[Route::MODULE] = $part[0];
                $parameters[Route::CONTROLLER] = $part[1];
                $parameters[Route::ACTION] = $part[2];
            }
            else
            {
                $parameters[Route::MODULE] = null;
                $parameters[Route::CONTROLLER] = $option;
                $parameters[Route::ACTION] = null;
            }
        };
        
        // Parse parameters
        foreach ($options as $key => $option)
        {
            switch ($key)
            {
                case Route::CALLABLE:
                    $parseCallable($option);
                    break;
                case Route::CONTROLLER:
                    if (explode('::', $option) === 3)
                    {
                        $parseCallable($option);
                    }
                    else
                    {
                        $parameters[Route::CONTROLLER] = $option;
                    }
                    break;
                case Route::QUERY:
                case Route::QUERY_ALIAS:
                    if (!is_array($option))
                    {
                        parse_str($option, $parsed);
                        $option = $parsed;
                    }
                    
                    $query = array_merge($query, $option);
                    break;
                case Route::REPLACEMENTS:
                case Route::REPLACEMENTS_ALIAS:
                    $replacements = array_merge($replacements, $option);
                    break;
                default:
                    $parameters[$key] = $option;
            }
        }
        
        // Start build path
        $path = $route->getPattern();
        
        // Replacements
        foreach ($replacements as $old => $new)
        {
            $path = str_replace('%' . $old . '%', $new, $path);
        }
        
        // Fix regex separator
        $path = str_replace('\\' . self::REGEX_SEPARATOR, self::REGEX_SEPARATOR, $path);
        
        $options = array_keys($route->getOptions());
        
        // Parse parameters
        foreach ($parameters as $key => $value)
        {
            if ($route->hasOption($key))
            {
                array_slice($options, array_search($key, $options), 1);
                $path = str_replace('{' . $key . '}', $value, $path);
            }
            else if (!$route->hasParameter($key) && $authorizeAttributes)
            {
                $attributes[$key] = $value;
            }
        }
        
        foreach ($options as $name)
        {
            if($name !== Route::ATTRIBUTES)
            {
                $path = str_replace('{' . $name . '}', $route->getParameter($name, ''), $path);
            }
        }
        
        // Clean expressions
        $clean = function($str)
        {
            return preg_replace_callback(
                '/\((.*)\)\?/U',
                function($matches)
                {
                    return $clean($matches[1]);
                }, 
                $str
            );
        };
        
        $path = preg_replace('/\((\/+)\)\?/U', '', $path);
        $path = trim($clean($path), '/');
        $path = $this->formatPath($path);
        
        if (in_array($mode, [self::URL_ABSOLUTE, self::SHEMA_RELATIVE]))
        {
            $baseURL = $this->request->getBaseURL();
            $url = rtrim($baseURL, '/') . '/' . ltrim($path, '/');
            
            if ($mode === self::SHEMA_RELATIVE)
            {
                $replace = '//';
            }
            else
            {
                if ((isset($options[Route::SECURE]) && $options[Route::SECURE]) || $route->hasOption(Route::SECURE))
                {
                    $replace = 'https://';
                }
                else
                {
                    $replace = $this->request->isSecure() ? 'https://' : 'http://';
                }
            }
            
            $url = preg_replace(
                '/^https?:\/\//',
                $replace,
                $url
            );
        }
        else
        {
            $url = $path;
        }
        
        return $url;
    }
    
    /**
     * @param string $path
     * @param array $attributes
     * @param array $query
     * @return string
     */
    protected function formatPath($path, array $attributes = [], array $query = [])
    {
        if (count($attributes) > 0)
        {
            $str = '';
            
            foreach ($attributes as $key => $value)
            {
                $str .= '/' . $key . '/' . rawurlencode($value);
            }
            
            $attributes = $str;
        }
        else
        {
            $attributes = '';
        }
        
        if (count($query) > 0)
        {
            $query = '?' . http_build_query($query);
        }
        else
        {
            $query = '';
        }
        
        $path = strtr(
            rawurlencode($path), 
            [
                '%2F' => '/',
                '%40' => '@',
                '%3A' => ':',
                '%3B' => ';',
                '%2C' => ',',
                '%3D' => '=',
                '%2B' => '+',
                '%21' => '!',
                '%2A' => '*',
                '%7C' => '|'
            ]
        );
        
        $path .= $attributes;
        $path .= $query;
        
        return $path;
    }
}
