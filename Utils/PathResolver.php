<?php namespace Rocket\Taxonomy\Utils;

use CentralDesktop\Graph\Edge\DirectedEdge;
use CentralDesktop\Graph\Graph\DirectedGraph;
use CentralDesktop\Graph\Vertex;

class PathResolver
{
    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array<integer>
     */
    protected $current_path;

    /**
     * @var DirectedGraph
     */
    protected $digraph;

    public function __construct(DirectedGraph $graph)
    {
        $this->digraph = $graph;
    }

    public function resolvePaths(Vertex $start_vertex)
    {
        $this->paths = [];

        /**
         * @var DirectedEdge
         */
        foreach ($start_vertex->incoming_edges as $edge) {
            $this->current_path = [$start_vertex->get_data()];
            $this->getPathsRecursion($edge->get_source(), $edge);
        }

        return $this->paths;
    }

    /**
     * @param Vertex $start
     * @param DirectedEdge $edge
     */
    protected function getPathsRecursion(Vertex $start, DirectedEdge $edge)
    {
        // We don't want to visit the same vertex twice within a single path. (avoid loops)
        if (in_array($start->get_data(), $this->current_path)) {
            $this->paths[] = array_reverse($this->current_path);

            return;
        }

        $this->current_path[] = $start->get_data();

        if ($start->incoming_edges->count() == 0) {
            $this->paths[] = array_reverse($this->current_path);

            return;
        }

        /**
         * @var DirectedEdge
         */
        foreach ($start->incoming_edges as $edge) {
            $this->getPathsRecursion($edge->get_source(), $edge);

            //remove the item that was added by the child
            array_pop($this->current_path);
        }
    }
}
