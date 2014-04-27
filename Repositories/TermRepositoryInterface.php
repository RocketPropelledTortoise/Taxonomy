<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 27.04.14
 * Time: 18:30
 */
namespace Rocket\Taxonomy\Repositories;

interface TermRepositoryInterface
{
    public function getTerm($term_id);
}
