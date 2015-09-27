<?php namespace Rocket\Taxonomy\Repositories;

use CentralDesktop\Graph\Graph\DirectedGraph;

/**
 * Create paths from a term all the way to all the parents.
 *
 * Everything is calculated upside down so that the DFS search for all paths is easy
 */
interface TermHierarchyRepositoryInterface
{
    /**
     * Get all ancestors
     * @return \Illuminate\Support\Collection
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
     * @return \Illuminate\Support\Collection
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
     * @param int $term_id
     * @param int $parent_id
     * @return bool
     */
    public function addParent($term_id, $parent_id);

    /**
     * @param int $term_id
     * @return bool
     */
    public function unsetParents($term_id);
}
