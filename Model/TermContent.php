<?php namespace Rocket\Taxonomy\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Relations from a term to a content
 *
 * @property int $term_id
 * @property int $relationable_id
 * @property string $relationable_type
 */
class TermContent extends Model
{
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_content';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['term_id'];

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
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function relationable()
    {
        return $this->morphTo();
    }
}
