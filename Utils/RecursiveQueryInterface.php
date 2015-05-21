<?php namespace Rocket\Taxonomy\Utils;

interface RecursiveQueryInterface
{
    /**
     * Get all ancestors of a term
     *
     * @param $id
     * @return \Illuminate\Support\Collection
     */
    public function getAncestry($id);

    /**
     * Get all descendents of a term
     *
     * @param $id
     * @return \Illuminate\Support\Collection
     */
    public function getDescent($id);
}
