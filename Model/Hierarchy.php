<?php namespace Rocket\Taxonomy\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Parent-child relations between terms
 *
 * @property int $term_id
 * @property int $parent_id
 */
class Hierarchy extends Model
{
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_term_hierarchy';

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\TermContainer', 'term_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\TermContainer', 'parent_id');
    }
}
