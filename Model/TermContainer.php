<?php namespace Rocket\Taxonomy\Model;

class TermContainer extends \Eloquent
{
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_terms';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['vocabulary_id', 'type'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany('Rocket\Taxonomy\Model\TermData', 'term_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vocabulary()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\Vocabulary', 'vocabulary_id');
    }
}
