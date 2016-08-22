<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Collection;
use Elixir\Routing\Request;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface MatcherInterface
{
    /**
     * @return Request
     */
    public function getRequest();

    /**
     * @param Collection $collection
     * @param string     $path
     *
     * @return RouteMatch|null
     */
    public function match(Collection $collection, $path = null);
}
