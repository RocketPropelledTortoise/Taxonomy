<?php namespace Rocket\Taxonomy\Repositories;

use CentralDesktop\Graph\Graph\DirectedGraph;
use CentralDesktop\Graph\Vertex;
use Illuminate\Support\Facades\DB;
use Rocket\Taxonomy\Model\Hierarchy;
use Rocket\Taxonomy\Utils\CommonTableExpressionQuery;
use Rocket\Taxonomy\Utils\PathResolver;
use Rocket\Taxonomy\Utils\RecursiveQuery;

/**
 * Create paths from a term all the way to all the parents.
 *
 * Everything is calculated upside down so that the DFS search for all paths is easy
 *
 * @package Rocket\Taxonomy
 */
class TermHierarchyRepository implements TermHierarchyRepositoryInterface
{

    /**
     * @var array<Vertex> all Vertices (Current and parents)
     */
    protected $vertices;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @param \Illuminate\Cache\Repository $cache
     */
    public function __construct(\Illuminate\Cache\Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param integer $term_id
     * @param integer $parent_id
     * @return bool
     */
    public function addParent($term_id, $parent_id)
    {
        return Hierarchy::insert(['term_id' => $term_id, 'parent_id' => $parent_id]);
    }

    /**
     * @param integer $term_id
     * @return bool
     */
    public function unsetParents($term_id)
    {
        return Hierarchy::where('term_id', $term_id)->delete();
    }

    protected function supportsCommonTableExpressionQuery()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver == 'mysql') {
            return true;
        }

        if ($driver == 'sqlite' && \SQLite3::version()['versionNumber'] >= 3008003) {
            return true;
        }

        return false;
    }

    /**
     * @return \Rocket\Taxonomy\Utils\RecursiveQueryInterface
     */
    protected function getRecursiveRetriever()
    {
        if ($this->supportsCommonTableExpressionQuery()) {
            return new CommonTableExpressionQuery();
        }

        return new RecursiveQuery();
    }

    /**
     * Get the hierarchy cache key
     *
     * @param string $direction
     * @param integer $id
     * @return string
     */
    protected function getCacheKey($direction, $id)
    {
        return "Rocket::Taxonomy::TermHierarchy::$direction::$id";
    }

    /**
     * Get all parents recursively
     *
     * @param int $id
     * @return \Illuminate\Support\Collection
     */
    public function getAncestry($id)
    {
        $key = $this->getCacheKey("ancestry", $id);
        if ($results = $this->cache->get($key)) {
            return $results;
        }

        $this->cache->add($key, $results = $this->getRecursiveRetriever()->getAncestry($id), 2);

        return $results;
    }

    /**
     * Get all childs recursively
     *
     * @param integer $id
     * @return \Illuminate\Support\Collection
     */
    public function getDescent($id)
    {
        $key = $this->getCacheKey("descent", $id);
        if ($results = $this->cache->get($key)) {
            return $results;
        }

        $this->cache->add($key, $results = $this->getRecursiveRetriever()->getDescent($id), 2);

        return $results;
    }

    protected function prepareVertices($data)
    {
        $vertices = [];
        foreach ($data as $content) {
            // identifiers must be strings or SplObjectStorage::contains fails
            // seems to impact only PHP 5.6
            $content->term_id = "$content->term_id";
            $content->parent_id = "$content->parent_id";

            if (!array_key_exists($content->term_id, $vertices)) {
                $vertices[$content->term_id] = new Vertex($content->term_id);
            }

            if (!array_key_exists($content->parent_id, $vertices)) {
                $vertices[$content->parent_id] = new Vertex($content->parent_id);
            }
        }
        return $vertices;
    }

    /**
     * Get all parents recursively
     *
     * @return array Vertex, DirectedGraph
     */
    public function getAncestryGraph($id)
    {
        $data = $this->getAncestry($id);

        if (count($data) == 0) {
            return [null, null];
        }

        // Create Vertices
        $this->vertices = $this->prepareVertices($data);

        // Create Graph
        $graph = new DirectedGraph();
        foreach ($this->vertices as $vertex) {
            $graph->add_vertex($vertex);
        }

        // Create Relations
        foreach ($data as $content) {
            $graph->create_edge($this->vertices[$content->parent_id], $this->vertices[$content->term_id]);
        }

        return [$this->vertices[$id], $graph];
    }

    /**
     * Get all childs recursively
     *
     * @return array Vertex, DirectedGraph
     */
    public function getDescentGraph($id)
    {
        $data = $this->getDescent($id);

        if (count($data) == 0) {
            return [null, null];
        }

        // Create Vertices
        $this->vertices = $this->prepareVertices($data);

        // Create Graph
        $graph = new DirectedGraph();
        foreach ($this->vertices as $vertex) {
            $graph->add_vertex($vertex);
        }

        // Create Relations
        foreach ($data as $content) {
            $graph->create_edge($this->vertices[$content->term_id], $this->vertices[$content->parent_id]);
        }

        return [$this->vertices[$id], $graph];
    }

    /**
     * Get all the possible paths from this term
     *
     * @return array<array<int>>
     */
    public function getAncestryPaths($id)
    {
        list($start_vertex, $graph) = $this->getAncestryGraph($id);

        if (!$graph) {
            return [];
        }

        $resolver = new PathResolver($graph);
        return $resolver->resolvePaths($start_vertex);
    }

    /**
     * Get all the possible paths from this term
     *
     * @return array<array<int>>
     */
    public function getDescentPaths($id)
    {
        list($start_vertex, $graph) = $this->getDescentGraph($id);

        if (!$graph) {
            return [];
        }

        $resolver = new PathResolver($graph);
        return $resolver->resolvePaths($start_vertex);
    }
}
