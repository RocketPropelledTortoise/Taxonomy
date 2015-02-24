<?php namespace Rocket\Taxonomy;

/**
 * Class Taxonomy
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    const TERM_CONTENT = 0;
    const TERM_CATEGORY = 1;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'taxonomy';
    }
}
