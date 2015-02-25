<?php namespace Rocket\Taxonomy\Repositories;

interface TermRepositoryInterface
{
    public function getTerm($term_id);

    public function uncacheTerm($term_id);
}
