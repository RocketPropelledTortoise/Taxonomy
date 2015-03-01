<?php namespace Rocket\Taxonomy\Utils;

interface RecursiveQueryInterface {
    public function getAncestry($ids);

    public function getDescent($ids);
}
