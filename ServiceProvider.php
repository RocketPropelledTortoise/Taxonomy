<?php
/**
 * Service Provider : Taxonomy
 */

namespace Rocket\Taxonomy;

/**
 * Taxonomy Service Provider
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app['taxonomy'] = $this->app->share(
            function ($app) {
                return new Taxonomy($app['cache']);
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['taxonomy'];
    }
}
