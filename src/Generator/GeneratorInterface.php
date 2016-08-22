<?php

namespace Elixir\Routing\Generator;

use Elixir\Routing\Request;
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
     * @var string
     */
    const SHEMA_RELATIVE = 'schema_relative';

    /**
     * @return Request
     */
    public function getRequest();

    /**
     * @param Route  $route
     * @param array  $options
     * @param string $mode
     *
     * @return string
     */
    public function generate(Route $route, array $options = [], $mode = self::URL_RELATIVE);
}
