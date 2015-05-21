<?php namespace Rocket\Taxonomy\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * The vocabularies in which you add terms
 *
 * @property integer $id
 * @property string $machine_name
 * @property string $description
 * @property integer $hierarchy
 * @property boolean $translatable
 */
class Vocabulary extends Model
{
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

    public function isTranslatable()
    {
        return (bool)$this->translatable;
    }
}
