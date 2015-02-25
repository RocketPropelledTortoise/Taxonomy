<?php namespace Rocket\Taxonomy;

use Illuminate\Support\Collection;
use Rocket\Taxonomy\Facade as T;
use Rocket\Taxonomy\Model\TermContainer;
use Illuminate\Support\Facades\Cache;

trait TaxonomyTrait
{

    abstract public function morphToMany(
        $related,
        $name,
        $table = null,
        $foreignKey = null,
        $otherKey = null,
        $inverse = false
    );
    abstract public function getTable();
    abstract public function getKey();

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function taxonomies()
    {
        $class = 'Rocket\Taxonomy\Model\TermContainer';
        return $this->morphToMany($class, 'relationable', 'taxonomy_content', null, 'term_id');
    }

    /**
     * Filter the model to return a subset of entries matching the term ID
     *
     * @param object $query
     * @param int $term_id
     *
     * @return void
     */
    public function scopeGetAllByTermId($query, $term_id)
    {
        return $query->whereHas('taxonomies', function($q) use ($term_id) {
            $q->where('term_id', $term_id);
        });
    }

    /**
     * Get the terms from a content
     *
     * @param  int|string $vocabulary_id
     * @return array
     */
    public function getTerms($vocabulary_id)
    {
        if (!$data = Cache::get($this->getTaxonomyCacheKey())) {
            $data = $this->cacheTermsForContent();
        }

        if (!is_numeric($vocabulary_id)) {
            $vocabulary_id = T::vocabulary($vocabulary_id);
        }

        $results = new Collection();
        if (array_key_exists($vocabulary_id, $data)) {
            foreach ($data[$vocabulary_id] as $term) {
                $results[] = T::getTerm($term);
            }
        }

        return $results;
    }

    public function addTerm($term_id)
    {
        TermContainer::findOrFail($term_id);

        // Cancel if the user wants to add the same term again
        if ($this->getTaxonomyQuery()->where('term_id', $term_id)->count()) {
            return;
        }

        $this->taxonomies()->attach($term_id);

        Cache::forget($this->getTaxonomyCacheKey());
    }

    /**
     * Set the terms to a content, removes the old ones (only for one vocabulary if specified)
     *
     * @param array <int> $terms
     * @param integer $vocabulary_id
     */
    public function setTerms($terms, $vocabulary_id = null)
    {
        $this->removeTerms($vocabulary_id);

        foreach ($terms as $term_id) {
            $this->addTerm($term_id);
        }
    }

    /**
     * Removes terms specified by a vocabulary, or all
     *
     * @param integer $vocabulary_id
     */
    public function removeTerms($vocabulary_id = null)
    {
        if ($vocabulary_id === null) {
            return $this->getTaxonomyQuery()->delete();
        }

        return $this->getTaxonomyQuery()->whereIn('term_id', function($query) use ($vocabulary_id) {
            $query->select('id')->where('vocabulary_id', $vocabulary_id)->from((new TermContainer)->getTable());
        })->delete();
    }

    /**
     * Cache all terms of a content (only id's)
     * @return array
     */
    private function cacheTermsForContent()
    {
        $term_container = (new TermContainer)->getTable();
        $terms = $this->taxonomies()
            ->select("$term_container.id", "$term_container.vocabulary_id")
            ->get();

        if (!count($terms)) {
            return [];
        }

        $results = [];
        foreach ($terms as $term) {
            $results[$term->vocabulary_id][] = $term->id;
        }

        // a whole week, because it's automatically recached
        Cache::put($this->getTaxonomyCacheKey(), $results, 60 * 24 * 7);

        return $results;
    }

    private function getTaxonomyCacheKey()
    {
        return 'Rocket::Taxonomy::Related::' . $this->getTable() . '::' . $this->getKey();
    }

    /**
     * Get a new plain query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function getTaxonomyQuery()
    {
        $t = $this->taxonomies();

        return $t->newPivotStatement()
            ->where($t->getForeignKey(), $this->getKey())
            ->where($t->getMorphType(), $t->getMorphClass());
    }
}
