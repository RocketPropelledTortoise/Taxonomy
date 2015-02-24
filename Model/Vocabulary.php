<?php namespace Rocket\Taxonomy\Model;

class Vocabulary extends \Eloquent {
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'taxonomy_vocabularies';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['name', 'machine_name', 'description', 'hierarchy', 'translatable'];

    function isTranslatable() {
        return (bool)$this->translatable;
    }
}
