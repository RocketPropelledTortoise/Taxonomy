<?php namespace Rocket\Taxonomy\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Rocket\Taxonomy\Model\Hierarchy;
use Illuminate\Support\Collection;

class RecursiveQuery implements RecursiveQueryInterface {

    protected $hierarchyTable;

    public function __construct()
    {
        $this->hierarchyTable = (new Hierarchy)->getTable();
    }

    public function getAncestry($id) {
        $all_results = new Collection(DB::select($this->getAncestryInitialQuery(), [':id' => $id]));

        if (count($all_results)) {
            $this->getRecursiveAncestry($all_results, $all_results->lists('parent_id'));
        }

        return $all_results;
    }

    protected function getAncestryInitialQuery()
    {
        return "select term_id, parent_id from $this->hierarchyTable where term_id = :id";
    }

    protected function getRecursiveAncestry(Collection $all_results, $ids)
    {
        $all_results->merge($results = DB::table($this->hierarchyTable)->whereIn('term_id', $ids)->get());

        if (count($results)) {
            $this->getRecursiveAncestry($all_results, Arr::fetch($results, 'parent_id'));
        }
    }

    public function getDescent($id) {

        $all_results = new Collection(DB::select($this->getDescentInitialQuery(), [':id' => $id]));

        if (count($all_results)) {
            $this->getRecursiveDescent($all_results, $all_results->lists('term_id'));
        }

        return $all_results->toArray();
    }

    protected function getDescentInitialQuery()
    {
        return "select term_id, parent_id from $this->hierarchyTable where parent_id = :id";
    }

    protected function getRecursiveDescent(Collection $all_results, $ids)
    {
        $all_results->merge($results = DB::table($this->hierarchyTable)->whereIn('parent_id', $ids)->get());

        if (count($results)) {
            $this->getRecursiveDescent($all_results, Arr::fetch($results, 'term_id'));
        }
    }
}
