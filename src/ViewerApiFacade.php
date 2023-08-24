<?php

namespace Rise3d\ViewerApi;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rise3d\ViewerApi\Skeleton\SkeletonClass
 */
class ViewerApiFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'viewer-api';
    }
}
