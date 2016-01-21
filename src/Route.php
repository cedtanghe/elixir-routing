<?php

namespace Elixir\Routing;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Route 
{
    /**
     * @var string
     */
    const NAME = 'name';
    
    /**
     * @var string
     */
    const NAME_ALIAS = 'as';
    
    /**
     * @var string
     */
    const PRIORITY = 'priority';
    
    /**
     * @var string
     */
    const PRIORITY_ALIAS = '+';
    
    /**
     * @var string
     */
    const PATTERN = 'pattern';
    
    /**
     * @var string
     */
    const PARAMETERS = 'parameters';
    
    /**
     * @var string
     */
    const OPTIONS = 'options';
    
    /**
     * @var string
     */
    const GLOBAL_CONFIG = 'global';
    
    /**
     * @var string
     */
    const CALLABLE = 'callable';
    
    /**
     * @var string
     */
    const MIDDLEWARES = 'middlewares';
    
    /**
     * @var string
     */
    const MODULE = 'module';

    /**
     * @var string
     */
    const CONTROLLER = 'controller';

    /**
     * @var string
     */
    const ACTION = 'action';

    /**
     * @var string
     */
    const SECURE = 'secure';
    
    /**
     * @var string
     */
    const SECURE_ALIAS = 'https';

    /**
     * @var string
     */
    const METHODS = 'methods';
    
    /**
     * @var string
     */
    const CONVERTERS = 'converters';

    /**
     * @var string
     */
    const ASSERT = 'assert';

    /**
     * @var string
     */
    const GENERATE_FILTER = 'generate_filter';

    /**
     * @var string
     */
    const MATCHED_FILTER = 'matched_filter';
    
    /**
     * @var string
     */
    const PREFIX = 'prefix';

    /**
     * @var string
     */
    const SUFFIX = 'suffix';

    /**
     * @var string
     */
    const REPLACEMENTS = 'replacements';

    /**
     * @var string
     */
    const REPLACEMENTS_ALIAS = '%';

    /**
     * @var string
     */
    const ATTRIBUTES = 'attributes';

    /**
     * @var string
     */
    const ATTRIBUTES_ALIAS = '*';

    /**
     * @var string
     */
    const QUERY = 'query';

    /**
     * @var string
     */
    const QUERY_ALIAS = '?';
    
    /**
     * @param string $key
     * @param Route|string $pattern
     * @return boolean
     */
    public static function isValidOption($key, $pattern = null)
    {
        $r = in_array($key, [
            self::MIDDLEWARES,
            self::CONVERTERS,
            self::SECURE,
            self::SECURE_ALIAS,
            self::METHOD,
            self::ATTRIBUTES,
            self::ATTRIBUTES_ALIAS,
            self::REPLACEMENTS,
            self::REPLACEMENTS_ALIAS,
            self::ASSERT,
            self::GENERATE_FILTER,
            self::MATCHED_FILTER,
            self::PREFIX,
            self::SUFFIX
        ]);
        
        if (!$r && $pattern)
        {
            $pattern = $pattern instanceof self ? $route->getPattern() : $pattern;
            return false !== strpos($pattern, '{' . $key . '}');
        }
        
        return $r;
    }

    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $pattern
     * @param array $parameters
     * @param array $options
     */
    public function __construct($pattern, array $parameters = [], array $options = [])
    {
        $this->pattern = trim($pattern, '/');
        $this->replaceParameters($parameters);
        $this->replaceOptions($options);
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }
    
    /**
     * @param string $prefix
     */
    public function prefix($prefix)
    {
        if (substr($this->pattern, 0, strlen($prefix)) != $prefix)
        {
            $this->pattern = $prefix . $this->pattern;
        }
    }
    
    /**
     * @param string $suffix
     */
    public function suffix($suffix)
    {
        if (substr($this->pattern, -strlen($suffix)) != $suffix)
        {
            $this->pattern = trim($this->pattern . $suffix, '/');
        }
    }
    
    /**
     * @param array|string $query
     */
    public function setQuery($query)
    {
        if (!is_array($query))
        {
            parse_str($query, $parsed);
            $query = $parsed;
        }
        
        $this->parameters[self::QUERY] = $query;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function hasParameter($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParameter($key, $default = null)
    {
        if ($this->hasParameter($key)) 
        {
            return $this->parameters[$key];
        }

        return is_callable($default) ? call_user_func($default) : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setParameter($key, $value)
    {
        switch ($key)
        {
            case self::CALLABLE:
                $this->setCallable($value);
                break;
            case self::QUERY:
            case self::QUERY_ALIAS:
                $this->setQuery($value);
                break;
            default:
                $this->parameters[$key] = $value;
        }
    }
    
    /**
     * @return array
     */
    public function allParameters()
    {
        return $this->parameters;
    }
    
    /**
     * @param array $parameters
     */
    public function replaceParameters(array $parameters)
    {
        $this->parameters = [];

        foreach ($parameters as $key => $value)
        {
            $this->setParameter($key, $value);
        }
    }
    
    /**
     * @param string $key
     */
    public function removeParameter($key)
    {
        unset($this->parameters[$key]);
    }
    
    /**
     * @param string|callable $callable
     * @throws \InvalidArgumentException
     */
    public function setCallable($callable)
    {
        if (is_callable($callable))
        {
            $this->setModule(null);
            $this->setController($callable);
            $this->setAction(null);
        }
        else
        {
            $parts = explode('::', $callable);

            if (count($parts) !== 3)
            {
                throw new \InvalidArgumentException(sprintf('Parameter "%s" is not valid.', self::CALLABLE));
            }
            
            $this->setModule($parts[0]);
            $this->setController($parts[1]);
            $this->setAction($parts[2]);
        }
    }
    
    /**
     * @param string $module
     */
    public function setModule($module)
    {
        $this->parameters[self::MODULE] = $module;
    }
    
    /**
     * @return string
     */
    public function getModule()
    {
        return $this->getParameter(self::MODULE);
    }

    /**
     * @param string|callable $controller
     */
    public function setController($controller)
    {
        if (is_string($controller) && count(explode('::', $controller)) === 3)
        {
            $this->setCallable($controller);
            return;
        }
        
        $this->parameters[self::CONTROLLER] = $controller;
    }
    
    /**
     * @return string|callable
     */
    public function getController()
    {
        return $this->getParameter(self::CONTROLLER);
    }
    
    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->parameters[self::ACTION] = $action;
    }
    
    /**
     * @return string
     */
    public function getAction()
    {
        return $this->getParameter(self::ACTION);
    }
    
    /**
     * @param string $key
     * @return boolean
     */
    public function hasOption($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if ($this->hasOption($key)) 
        {
            return $this->options[$key];
        }

        return is_callable($default) ? call_user_func($default) : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setOption($key, $value)
    {
        switch ($key) 
        {
            case self::CONVERTERS:
                $this->options[self::CONVERTERS] = [];
                
                foreach ($value as $k => $v)
                {
                    $this->convert($k, $v);
                }
                break;
            case self::MIDDLEWARES:
                $this->options[self::MIDDLEWARES] = [];
                
                foreach ($value as $middleware)
                {
                    $this->pipe($middleware);
                }
                break;
            case self::SECURE:
            case self::SECURE_ALIAS:
                $this->setSecure($value);
                break;
            case self::METHOD:
                $this->setMethods($value);
                break;
            case self::ATTRIBUTES:
            case self::ATTRIBUTES_ALIAS:
                $this->setUseAttributes($value);
                break;
            case self::REPLACEMENTS:
            case self::REPLACEMENTS_ALIAS:
                $this->setReplacements($value);
                break;
            case self::ASSERT:
                $this->setAssertion($value);
                break;
            case self::GENERATE_FILTER:
                $this->setGenerateFilter($value);
                break;
            case self::MATCHED_FILTER:
                $this->setMatchedFilter($value);
                break;
            case self::PREFIX:
                $this->prefix($value);
                break;
            case self::SUFFIX:
                $this->suffix($value);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('There is no option named "%s".', $key));
        }
    }
    
    /**
     * @return array
     */
    public function allOptions()
    {
        return $this->options;
    }
    
    /**
     * @param array $options
     */
    public function replaceOptions(array $options)
    {
        $this->options = [];

        foreach ($options as $key => $value)
        {
            $this->setOption($key, $value);
        }
    }
    
    /**
     * @param string $key
     */
    public function removeOption($key)
    {
        unset($this->options[$key]);
    }
    
    /**
     * @param string $key
     * @param mixed $converter
     */
    public function convert($key, $converter)
    {
        $this->options[self::CONVERTERS][] = $converter;
    }
    
    /**
     * @return array
     */
    public function getConverters()
    {
        return $this->getOption(self::CONVERTERS, []);
    }
    
    /**
     * @param callable $middleware
     */
    public function pipe(callable $middleware)
    {
        $this->options[self::MIDDLEWARES][] = $middleware;
    }
    
    /**
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->getOption(self::MIDDLEWARES, []);
    }
    
    /**
     * @param boolean $value
     */
    public function setSecure($value)
    {
        $this->options[self::SECURE] = $value;
    }
    
    /**
     * @return boolean
     */
    public function isSecure()
    {
        return $this->getOption(self::SECURE, false);
    }
    
    /**
     * @param string|array $value
     * @throws \InvalidArgumentException
     */
    public function setMethods($value)
    {
        $methods = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PATCH'];
        $data = (array)$value;
        
        foreach($data as &$method)
        {
            if(!in_array(strtoupper($method), $methods))
            {
                throw new \InvalidArgumentException(sprintf('Options parameter "%s" is not valid.', self::METHOD));
            }
        }

        $this->options[self::METHOD] = $data;
    }
    
    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->getOption(self::METHOD, []);
    }
    
    /**
     * @param boolean $pValue
     */
    public function setUseAttributes($value)
    {
        if ($value)
        {
            $this->options[self::ATTRIBUTES] = '(/.+)?';
        }
        else
        {
            $this->removeOption(self::ATTRIBUTES);
        }
    }
    
    /**
     * @return boolean
     */
    public function isUseAttributes()
    {
        return false !== $this->getOption(self::ATTRIBUTES, false);
    }
    
    /**
     * @param array $replacement
     */
    public function setReplacements(array $replacement)
    {
        $this->options[self::REPLACEMENTS] = $replacement;
    }
    
    /**
     * @return array
     */
    public function getReplacements()
    {
        return $this->getOption(self::REPLACEMENTS, []);
    }
    
    /**
     * @param callable $assert
     */
    public function setAssertion(callable $assert)
    {
        $this->options[self::ASSERT] = $assert;
    }
    
    /**
     * @return callable
     */
    public function  getAssertion()
    {
        return $this->getOption(self::ASSERT);
    }
    
    /**
     * @param callable $filter
     */
    public function setGenerateFilter(callable $filter)
    {
        $this->options[self::GENERATE_FILTER] = $filter;
    }
    
    /**
     * @return callable
     */
    public function getGenerateFilter()
    {
        return $this->getOption(self::GENERATE_FILTER);
    }
    
    /**
     * @param callable $filter
     */
    public function setMatchedFilter(callable $filter)
    {
        $this->options[self::MATCHED_FILTER] = $filter;
    }
    
    /**
     * @return callable
     */
    public function getMatchedFilter()
    {
        return $this->getOption(self::MATCHED_FILTER);
    }
    
    /**
     * @ignore
     */
    public function __debugInfo()
    {
        return [
            'pattern' => $this->pattern,
            'parameters' => $this->parameters,
            'options' => $this->options
        ];
    }
}
