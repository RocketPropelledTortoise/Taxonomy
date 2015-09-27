<?php namespace Rocket\Taxonomy\Repositories;

interface TermRepositoryInterface
{
    /**
     * Get a term with all translations
     *
     * @param int $term_id
     * @return \Rocket\Taxonomy\Term
     */
    public function getTerm($term_id);

    /**
     * Remove a term from the cache
     *
     * @param int $term_id
     * @return bool
     */
    public function uncacheTerm($term_id);
}
