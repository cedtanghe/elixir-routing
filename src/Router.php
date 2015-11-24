<?php

namespace Elixir\Routing;

use Elixir\Routing\Collection;
use Elixir\Routing\Generator\GeneratorInterface;
use Elixir\Routing\Matcher\MatcherInterface;
use Elixir\Routing\RouterInterface;
use Elixir\Routing\RouterUtilTrait;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Router implements RouterInterface 
{
    use RouterUtilTrait;
    
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
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function match($path)
    {
        if(null === $this->matcher)
        {
            throw new \RuntimeException('Matcher implementation is not defined.');
        }
        
        $pathInfos = trim($path, '/');
        return $this->matcher->match($this->collection, $pathInfos);
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
     * @ignore
     */
    public function __debugInfo()
    {
        return $this->collection;
    }
}
