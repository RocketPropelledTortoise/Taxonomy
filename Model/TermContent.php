<?php namespace Rocket\Taxonomy\Model;

class TermContent extends \Eloquent
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\TermContainer', 'term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function relationable()
    {
        return $this->morphTo();
    }
}
