<?php namespace Rocket\Taxonomy\Utils;

use Illuminate\Support\Facades\DB;

class CommonTableExpressionQuery extends RecursiveQuery implements RecursiveQueryInterface
{

    protected function runQuery($raw_query, $id)
    {
        $query = str_replace(':id', $id, $raw_query);

        $start = microtime(true);

        // Does not work as a prepared statement; we have to execute it directly
        $results = DB::getReadPdo()->query($query)->fetchAll(\PDO::FETCH_OBJ);

        // Log the query manually
        DB::logQuery($raw_query, [$id], round((microtime(true) - $start) * 1000, 2));

        return $results;
    }

    protected function assembleQuery($initial, $recursive)
    {
        $tmp_tbl = 'name_tree';
        $recursive = str_replace(':tmp_tbl', $tmp_tbl, $recursive);

        $final = "select distinct * from $tmp_tbl";

        if (DB::connection()->getDriverName() == 'mysql') {
            return "Call WITH_EMULATOR('$tmp_tbl', '$initial', '$recursive', '$final', 0, 'ENGINE=MEMORY');";
        }

        return "WITH RECURSIVE $tmp_tbl AS ($initial UNION ALL $recursive) $final;";
    }

    public function getAncestry($id)
    {
        $tbl = $this->hierarchyTable;
        $recursive = "SELECT c.term_id, c.parent_id from $tbl as c join :tmp_tbl as p on p.parent_id = c.term_id";

        $raw_query = $this->assembleQuery($this->getAncestryInitialQuery(), $recursive);

        return $this->runQuery($raw_query, $id);
    }

    public function getDescent($id)
    {
        $tbl = $this->hierarchyTable;
        $recursive = "SELECT c.term_id, c.parent_id from $tbl as c join :tmp_tbl as p on c.parent_id = p.term_id";

        $raw_query = $this->assembleQuery($this->getDescentInitialQuery(), $recursive);

        return $this->runQuery($raw_query, $id);
    }
}
