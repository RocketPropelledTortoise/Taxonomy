<?php namespace Rocket\Taxonomy\Repositories;

use CentralDesktop\Graph\Graph\DirectedGraph;
use CentralDesktop\Graph\Vertex;
use DB;
use Rocket\Taxonomy\Model\Hierarchy;
use Rocket\Taxonomy\PathResolver;

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
     * Get the SQL Query to run on the database to get the recursive content
     *
     * @return string
     */
    protected function getQuery($direction)
    {
        //TODO :: also support real "WITH RECURSIVE" syntax
        //TODO :: also add fallback to simple recursive calls

        $hierarchy_table = (new Hierarchy)->getTable();
        $temp_name = 'name_tree';

        $recursive_base = "select `c`.`term_id`, `c`.`parent_id` from `$hierarchy_table` as `c`";

        if ($direction == 'ancestry') {
            $initial = "select `term_id`, `parent_id` from `$hierarchy_table` where `term_id` = :id";
            $recursive = "$recursive_base join `$temp_name` as `p` on `p`.`parent_id` = `c`.`term_id`";
        } else {
            $initial = "select `term_id`, `parent_id` from `$hierarchy_table` where `parent_id` = :id";
            $recursive = "$recursive_base join `$temp_name` as `p` on `c`.`parent_id` = `p`.`term_id`";
        }

        $final = "select distinct * from `$temp_name`";


        return "Call WITH_EMULATOR('$temp_name', '$initial', '$recursive', '$final', 0, 'ENGINE=MEMORY');";
    }

    /**
     * Get all parents recursively from database
     *
     * @param int $id
     * @param string $direction
     * @return array
     */
    protected function getRawData($id, $direction)
    {
        return $this->cache->remember(
            "Rocket::Taxonomy::TermHierarchy::$direction::$id",
            2,
            function () use ($id, $direction) {
                $raw_query = $this->getQuery($direction);
                $query = str_replace(':id', $id, $raw_query);

                $start = microtime(true);

                //does not work as a prepared statement; we have to execute it directly
                $results = DB::getReadPdo()->query($query)->fetchAll(\PDO::FETCH_OBJ);

                //we can't run it through laravel but the best is still to store the results
                DB::logQuery($raw_query, [$id], round((microtime(true) - $start) * 1000, 2));

                return $results;
            }
        );
    }

    protected function prepareVertices($data)
    {
        $vertices = [];
        foreach ($data as $content) {
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
    public function getAncestry($id)
    {
        $data = $this->getRawData($id, 'ancestry');

        if (empty($data)) {
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
    public function getDescent($id)
    {
        $data = $this->getRawData($id, 'descent');

        if (empty($data)) {
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
        list($start_vertex, $graph) = $this->getAncestry($id);

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
        list($start_vertex, $graph) = $this->getDescent($id);

        if (!$graph) {
            return [];
        }

        $resolver = new PathResolver($graph);
        return $resolver->resolvePaths($start_vertex);
    }
}
