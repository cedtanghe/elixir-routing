<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Collection;
use Elixir\Routing\Matcher\RouteMatch;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface MatcherInterface 
{
    /**
     * @param Collection $routes
     * @param string $path
     * @return RouteMatch|null
     */
    public function match(Collection $routes, $path);
}
