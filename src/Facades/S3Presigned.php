<?php

namespace Unisharp\S3\Presigned\Facades;

use Illuminate\Support\Facades\Facade;

class S3Presigned extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 's3.presigned';
    }
}