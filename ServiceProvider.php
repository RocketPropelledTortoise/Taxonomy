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
        $this->app->bind('Rocket\Taxonomy\Repositories\TermRepositoryInterface', 'Rocket\Taxonomy\Repositories\TermRepository');
        $this->app->bind('Rocket\Taxonomy\Repositories\TermHierarchyRepositoryInterface', 'Rocket\Taxonomy\Repositories\TermHierarchyRepository');

        $this->app['taxonomy'] = $this->app->share(
            function ($app) {
                return $this->app->make('\Rocket\Taxonomy\Taxonomy');
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
