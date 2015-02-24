<?php namespace Rocket\Taxonomy\Repositories;

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
     * Get all parents
     * @return DirectedGraph|null
     */
    public function getDescent($id);

    /**
     * @return array
     */
    public function getAncestryPaths($id);

    /**
     * @return array
     */
    public function getDescentPaths($id);
}
