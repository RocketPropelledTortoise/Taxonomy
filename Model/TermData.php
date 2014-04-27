<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 05.03.14
 * Time: 22:36
 */

namespace Rocket\Taxonomy\Model;


class TermData extends \Eloquent {
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_terms_data';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo('Rocket\Taxonomy\Model\TermContainer', 'term_id');
    }
}
