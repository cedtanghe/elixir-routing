<?php

namespace Elixir\Routing\Generator;

use Elixir\Routing\Generator\URLGenerator;
use Elixir\Routing\Request;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class QueryGenerator extends URLGenerator 
{
    /**
     * @var string 
     */
    protected $queryKey;
    
    /**
     * @param Request $request
     */
    public function __construct(Request $request, $queryKey = 'r')
    {
        $this->queryKey = $queryKey;
        parent::__construct($request);
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
    protected function formatPath($path, array $attributes = [], array $query = [])
    {
        $attributes = '';
        
        if (count($attributes) > 0)
        {
            $str = '';
            
            foreach ($attributes as $key => $value)
            {
                $str .= '/' . $key . '/' . rawurlencode($value);
            }
            
            $attributes = $str;
        }
        
        $query[$this->queryKey] = $path . $attributes;
        $path = '';
        
        if (isset($query[Route::SID]))
        {
            $path = '?' . $query[Route::SID];
            unset($query[Route::SID]);
        }
        
        $path .= (0 === strpos('?', $path) ? '&' : '?') . strtr(
            http_build_query($query),
            [
                '%2F' => '/',
                '%40' => '@',
                '%3A' => ':',
                '%3B' => ';',
                '%2C' => ',',
                '%3D' => '=',
                '%2B' => '+',
                '%21' => '!',
                '%2A' => '*',
                '%7C' => '|'
            ]
        );
        
        return $path;
    }
}
