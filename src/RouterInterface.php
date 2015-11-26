<?php

namespace Elixir\Routing;

use Elixir\Routing\Collection;
use Elixir\Routing\Generator\GeneratorInterface;
use Elixir\Routing\Matcher\MatcherInterface;
use Elixir\Routing\Matcher\RouteMatch;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface RouterInterface
{
    /**
     * @return Collection
     */
    public function getCollection();

    /**
     * @return MatcherInterface
     */
    public function getMatcher();
    
    /**
     * @return GeneratorInterface
     */
    public function getGenerator();
    
    /**
     * @param string $path
     * @return RouteMatch|null
     */
    public function match($path = null);
    
    /**
     * @param string $name
     * @param array $options
     * @param string $mode
     * @return string
     */
    public function generate($name, array $options = [], $mode = GeneratorInterface::URL_RELATIVE);
}
