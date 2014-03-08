<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 02.03.14
 * Time: 21:35
 */

namespace Rocket\Taxonomy\Model;

class Term extends \Eloquent
{
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_terms';

    public function translations()
    {
        return $this->hasMany('Rocket\Taxonomy\Model\TermData', 'term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parents()
    {
        return $this->belongsToMany('Rocket\Taxonomy\Model\Term', 'taxonomy_term_hierarchy', 'term_id', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vocabulary()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\Vocabulary', 'vocabulary_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function contents()
    {
        return $this->belongsToMany('Content', 'taxonomy_content', 'term_id', 'content_id');
    }


}
