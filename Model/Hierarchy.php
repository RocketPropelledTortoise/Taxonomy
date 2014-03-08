<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 05.03.14
 * Time: 22:18
 */

namespace Rocket\Taxonomy\Model;


class Hierarchy extends \Eloquent {
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_term_hierarchy';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\Term', 'term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\Term', 'parent_id');
    }
}
