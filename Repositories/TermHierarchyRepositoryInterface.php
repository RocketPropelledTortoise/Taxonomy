<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 27.04.14
 * Time: 21:37
 */
namespace Rocket\Taxonomy\Repositories;

use CentralDesktop\Graph\Graph\DirectedGraph;


/**
 * Create paths from a term all the way to all the parents.
 *
 * Everything is calculated upside down so that the DFS search for all paths is easy
 *
 * @package Rocket\Taxonomy
 */
interface TermHierarchyRepositoryInterface
{
    /**
     * Get all parents
     * @return DirectedGraph|null
     */
    public function getAncestry($id);

    /**
     * @return array
     */
    public function getPaths($id);
}
