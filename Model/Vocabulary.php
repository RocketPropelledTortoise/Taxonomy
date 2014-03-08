<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 02.03.14
 * Time: 21:35
 */

namespace Rocket\Taxonomy\Model;

class Vocabulary extends \Eloquent {
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_vocabularies';
}
