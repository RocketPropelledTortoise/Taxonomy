<?php namespace Rocket\Taxonomy\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * The main term container
 *
 * @property int $id
 * @property int $vocabulary_id
 * @property int $type
 */
class TermContainer extends Model
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
