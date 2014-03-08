<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 06.03.14
 * Time: 23:09
 */

namespace Rocket\Taxonomy\Model;


class TermContent extends \Eloquent {
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_content';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term() {
        return $this->belongsTo('Rocket\Taxonomy\Model\Term', 'term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function content() {
        return $this->belongsTo('Content', 'content_id');
    }
}
