<?php

namespace Elixir\Routing;

use Elixir\HTTP\ServerRequest;
use Elixir\HTTP\URI;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class RequestContext
{
    /**
     * @param ServerRequest $request
     * @return RequestContext
     */
    public static function fromServerRequest(ServerRequest $request)
    {
        $config = [
            'base_url' => $request->getBaseURL(),
            'method' => $request->getMethod(),
            'scheme' => $request->getScheme(),
            'path_info' => $request->getPathInfo(),
            'query_string' => $request->getServer('QUERY_STRING', ''),
            'parameters' => $request->getAttributes() + ['_request' => $request]
        ];
        
        return new static($config);
    }
    
    /**
     * @param URI $URI
     * @return RequestContext
     */
    public static function fromURI(URI $URI)
    {
        $config = [
            'base_url' => URI::buildURIString(['scheme' => $URI->getScheme(), 'authority' => $URI->getAuthority()]),
            'method' => 'GET',
            'scheme' => $URI->getScheme(),
            'path_info' => $URI->getPath(),
            'query_string' => $URI->getQuery() ?: '',
            'parameters' => ['_uri' => $URI]
        ];
        
        return new static($config);
    }
    
    /**
     * @var string
     */
    protected $baseURL;
    
    /**
     * @var string
     */
    protected $method;
    
    /**
     * @var string
     */
    protected $scheme;
    
    /**
     * @var string
     */
    protected $pathInfo;
    
    /**
     * @var string
     */
    protected $queryString;
    
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $config += [
            'base_url' => '',
            'method' => 'GET',
            'scheme' => 'http',
            'path_info' => '/',
            'query_string' => '',
            'parameters' => []
        ];
        
        $this->baseURL = $config['base_url'];
        $this->method = $config['method'];
        $this->scheme = $config['scheme'];
        $this->pathInfo = $config['path_info'];
        $this->queryString = $config['query_string'];
        $this->parameters = $config['parameters'];
    }
    
    /**
     * @return string 
     */
    public function getBaseURL()
    {
        return $this->baseURL;
    }
    
    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * @return string 
     */
    public function getScheme()
    {
        return $this->scheme;
    }
    
    /**
     * @return string 
     */
    public function getPathInfo()
    {
        return $this->pathInfo;
    }
    
    /**
     * @return string 
     */
    public function getQueryString()
    {
        return $this->queryString;
    }
    
    /**
     * @return boolean 
     */
    public function isSecure()
    {
        return $this->getScheme() === 'https';
    }
    
    /**
     * @return string 
     */
    public function getURL()
    {
        $url = rtrim($this->getBaseURL(), '/');
        
        if (!empty($this->getPathInfo()))
        {
            $url .= '/' . ltrim($this->getPathInfo(), '/');
        }
        
        if (!empty($this->getQueryString()))
        {
            $url .= '?' . $this->getQueryString();
        }
        
        return $url;
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
     * @return array
     */
    public function allParameters()
    {
        return $this->parameters;
    }
    
    /**
     * @ignore
     */
    public function __call($method, $arguments) 
    {
        if ($this->hasParameter('_request'))
        {
            return call_user_func_array($this->parameters['_request'], $arguments);
        }
        else if ($this->hasParameter('_uri'))
        {
            return call_user_func_array($this->parameters['_uri'], $arguments);
        }
        
        throw new \BadMethodCallException(sprintf('Method "%s" is undefined.'));
    }
}
