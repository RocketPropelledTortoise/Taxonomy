<?php namespace Rocket\Taxonomy\Model;

use Rocket\Taxonomy\Facade as T;

class TermData extends \Eloquent
{
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_terms_data';

    /**
     * When used in a term, we set this to true or false
     */
    public $translated = true;

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\TermContainer', 'term_id');
    }

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['term_id', 'language_id', 'title', 'description'];

    /**
     * {@inheritdoc}
     */
    public function save(array $options = array())
    {
        if ($this->translated == false) {
            $this->translated = true;
        }

        T::uncacheTerm($this->term_id);

        parent::save($options);
    }
}
