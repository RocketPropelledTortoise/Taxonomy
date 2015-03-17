<?php namespace Rocket\Taxonomy\Support\Laravel5;

/**
 * Taxonomy Service Provider
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['taxonomy'];
    }
}
