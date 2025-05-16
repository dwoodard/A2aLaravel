<?php

namespace Dwoodard\A2aLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class A2a extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'a2a'; // This should match the binding key in your service provider
    }
}
