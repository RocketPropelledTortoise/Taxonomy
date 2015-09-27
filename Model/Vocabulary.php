<?php namespace Rocket\Taxonomy\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * The vocabularies in which you add terms
 *
 * @property int $id
 * @property string $machine_name
 * @property string $description
 * @property int $hierarchy
 * @property bool $translatable
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
        return (bool) $this->translatable;
    }
}
