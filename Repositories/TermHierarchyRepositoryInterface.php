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
     * Get all ancestors
     * @return array
     */
    public function getAncestry($id);

    /**
     * Get all ancestors in a graph
     * @return array Vertex, DirectedGraph
     */
    public function getAncestryGraph($id);

    /**
     * @return array
     */
    public function getAncestryPaths($id);

    /**
     * Get all descendants
     * @return array
     */
    public function getDescent($id);

    /**
     * Get all descendants in a graph
     * @return array Vertex, DirectedGraph
     */
    public function getDescentGraph($id);

    /**
     * @return array
     */
    public function getDescentPaths($id);

    /**
     * @param integer $term_id
     * @param integer $parent_id
     * @return bool
     */
    public function addParent($term_id, $parent_id);

    /**
     * @param integer $term_id
     * @return bool
     */
    public function unsetParents($term_id);
}
