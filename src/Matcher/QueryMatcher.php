<?php

namespace Elixir\Routing\Matcher;

use Elixir\Routing\Collection;
use Elixir\Routing\Matcher\URLMatcher;
use Elixir\Routing\RequestContext;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */

class QueryMatcher extends URLMatcher
{
    /**
     * @var string 
     */
    protected $queryKey;

    /**
     * @param RequestContext $request
     */
    public function __construct(RequestContext $request, $queryKey = 'r')
    {
        $this->request = $request;
        $this->queryKey = $queryKey;
    }

    /**
     * @return string
     */
    public function getQueryKey()
    {
        return $this->queryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Collection $collection, $path = null)
    {
        parse_str($path ?: rawurldecode($this->request->getQueryString()), $q);
        
        if (isset($q[$this->queryKey]))
        {
            $path = $q[$this->queryKey];
        }
        else
        {
            $path = '';
        }
        
        return parent::match($collection, $path);
    }
}
