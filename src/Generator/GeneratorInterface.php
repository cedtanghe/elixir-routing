<?php

namespace Elixir\Routing\Generator;

use Elixir\Routing\RequestContext;
use Elixir\Routing\Route;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface GeneratorInterface
{
    /**
     * @var string
     */
    const URL_RELATIVE = 'url_relative';
    
    /**
     * @var string
     */
    const URL_ABSOLUTE = 'url_absolute';
    
    /**
     * @return RequestContext
     */
    public function getRequestContext();
    
    /**
     * @param Route $route
     * @param array $options
     * @param string $mode
     * @return string
     */
    public function generate(Route $route, array $options = [], $mode = GeneratorInterface::URL_RELATIVE);
}
