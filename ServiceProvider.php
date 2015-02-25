<?php namespace Rocket\Taxonomy;

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
        $prefix = 'Rocket\Taxonomy\Repositories';
        $this->app->bind("$prefix\TermRepositoryInterface", "$prefix\TermRepository");
        $this->app->bind("$prefix\TermHierarchyRepositoryInterface", "$prefix\TermHierarchyRepository");

        $this->app['taxonomy'] = $this->app->share(
            function ($app) {
                return $app->make('\Rocket\Taxonomy\Taxonomy');
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
