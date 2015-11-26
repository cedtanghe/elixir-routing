<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Collection;
use Elixir\Routing\Matcher\RouteMatch;
use Elixir\Routing\RequestContext;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface MatcherInterface 
{
    /**
     * @return RequestContext
     */
    public function getRequestContext();
    
    /**
     * @param Collection $collection
     * @param string $path
     * @return RouteMatch|null
     */
    public function match(Collection $collection, $path = null);
}
