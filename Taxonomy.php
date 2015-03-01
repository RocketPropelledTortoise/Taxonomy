<?php namespace Rocket\Taxonomy;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Rocket\Taxonomy\Model\TermContainer;
use Rocket\Taxonomy\Model\TermData;
use Rocket\Taxonomy\Model\Vocabulary;
use Rocket\Taxonomy\Repositories\TermHierarchyRepositoryInterface as TermHieraRep;
use Rocket\Taxonomy\Repositories\TermRepositoryInterface as TermRep;
use Rocket\Translation\I18NFacade as I18N;

/**
 * Class Taxonomy
 */
class Taxonomy
{
    /**
     * Terms cache
     *
     * @var array
     */
    public $terms = [];

    /**
     * List of vocabularies by ID
     *
     * @var array
     */
    protected $vocabularyById = [];

    /**
     * List of vocabularies by Name
     *
     * @var array
     */
    protected $vocabularyByName = [];

    /**
     * @var CacheRepository
     */
    protected $cache;

    /**
     * @var TermRep
     */
    protected $termRepository;

    /**
     * @var TermHieraRep
     */
    protected $termHierarchyRepository;

    /**
     * Initialize the taxonomy, Loads all existing vocabularies
     */
    public function __construct(CacheRepository $cache, TermRep $termRepository, TermHieraRep $termHierarchyRepository)
    {
        $this->termRepository = $termRepository;
        $this->termHierarchyRepository = $termHierarchyRepository;
        $this->cache = $cache;

        // Get the list of vocabularies
        $vocs = $cache->remember('Rocket::Taxonomy::Vocabularies', 60 * 24 * 7, function() {
            return Vocabulary::all();
        });

        // Initialize the search for terms
        foreach ($vocs as $v) {
            $this->vocabularyByName[$v->machine_name] = $this->vocabularyById[$v->id] = $v;
        }
    }

    /**
     * Is this vocabulary translatable ?
     *
     * @param string|int $vid
     * @return boolean
     */
    public function isTranslatable($vid)
    {
        if (!is_numeric($vid)) {
            $vid = $this->vocabulary($vid);
        }

        return $this->vocabularyById[$vid]->isTranslatable();
    }

    /**
     * Get the internal language for the vocabulary
     *
     * This will return the language_id if the vocabulary is translated or 1 if it's not
     *
     * @param integer|string $vocabulary_id
     * @param integer $language_id
     * @return integer|null
     */
    public function getLanguage($vocabulary_id, $language_id = null)
    {
        if (!$this->isTranslatable($vocabulary_id)) {
            return 1;
        }

        if ($language_id == null) {
            return I18N::getCurrentId();
        }

        return $language_id;
    }

    /**
     * Get a vocabulary by name or ID
     *
     *     Taxonomy::vocabulary(1);
     *     returns 'tags'
     *
     *     Taxonomy::vocabulary('tags');
     *     returns 1
     *
     * @param $key
     * @return mixed
     */
    public function vocabulary($key)
    {
        if (is_numeric($key)) {
            return $this->vocabularyById[$key]->machine_name;
        }

        return $this->vocabularyByName[$key]->id;
    }

    /**
     * Get all vocabularies with keys as id's
     *
     * @return array
     */
    public function vocabularies()
    {
        return $this->vocabularyById;
    }

    /**
     * Get a term with all translations
     *
     * @param  integer $term_id
     * @return Term
     */
    public function getTerm($term_id, $from_cache = true)
    {
        if ($from_cache && array_key_exists($term_id, $this->terms)) {
            return $this->terms[$term_id];
        }

        $data = $this->termRepository->getTerm($term_id);
        $this->terms[$term_id] = $data;

        return $data;
    }

    /**
     * Remove a term from the cache
     *
     * @param integer $term_id
     * @return bool
     */
    public function uncacheTerm($term_id)
    {
        if (array_key_exists($term_id, $this->terms)) {
            unset($this->terms[$term_id]);
        }

        return $this->termRepository->uncacheTerm($term_id);
    }

    /**
     * Get all paths for a term
     *
     * @param int $term_id
     * @return array<array<int>>
     */
    public function getAncestryPaths($term_id)
    {
        return $this->termHierarchyRepository->getAncestryPaths($term_id);
    }

    /**
     * Get all paths for a term
     *
     * @param int $term_id
     * @return array<array<int>>
     */
    public function getDescentPaths($term_id)
    {
        return $this->termHierarchyRepository->getDescentPaths($term_id);
    }

    /**
     * Get the complete graph
     * @param $term_id
     * @return array
     */
    public function getAncestryGraph($term_id)
    {
        return $this->termHierarchyRepository->getAncestryGraph($term_id);
    }

    /**
     * Get the complete graph
     * @param $term_id
     * @return array
     */
    public function getDescentGraph($term_id)
    {
        return $this->termHierarchyRepository->getDescentGraph($term_id);
    }

    /**
     * @param integer $term_id
     * @param integer $parent_id
     * @return bool
     */
    public function addParent($term_id, $parent_id)
    {
        return $this->termHierarchyRepository->addParent($term_id, $parent_id);
    }

    /**
     * @param integer $term_id
     * @return bool
     */
    public function unsetParents($term_id)
    {
        return $this->termHierarchyRepository->unsetParents($term_id);
    }

    /**
     * Get all the terms of a vocabulary
     *
     * @param  integer $vocabulary_id
     * @return array
     */
    public function getTermsForVocabulary($vocabulary_id)
    {
        return $this->cache->remember(
            'Rocket::Taxonomy::Terms::' . $vocabulary_id,
            60,
            function () use ($vocabulary_id) {
                $terms = TermContainer::where('vocabulary_id', $vocabulary_id)->get(['id']);

                $results = [];
                if (!empty($terms)) {
                    foreach ($terms as $term) {
                        $results[] = $term->id;
                    }
                }

                return $results;
            }
        );
    }

    /**
     * Search a specific term, if it doesn't exist, returns false
     *
     * @param  string $term
     * @param  int $vocabulary_id
     * @param  int $language_id
     * @param  array $exclude
     * @return int|null
     */
    public function searchTerm($term, $vocabulary_id, $language_id = null, $exclude = [])
    {
        $language_id = $this->getLanguage($vocabulary_id, $language_id);

        $term = trim($term);
        if ($term == '') {
            return null;
        }

        $query = TermData::select('taxonomy_terms.id')
            ->join('taxonomy_terms', 'taxonomy_terms.id', '=', 'taxonomy_terms_data.term_id')
            ->where('taxonomy_terms.vocabulary_id', $vocabulary_id)
            ->where('taxonomy_terms_data.language_id', $language_id)
            ->where('taxonomy_terms_data.title', $term);

        if (count($exclude)) {
            $query->whereNotIn('taxonomy_terms.id', $exclude);
        }

        return $query->pluck('id');
    }

    /**
     * Returns the id of a term, if it doesn't exist, creates it.
     *
     * @param  string $title
     * @param  int $vocabulary_id
     * @param  int $language_id
     * @param  int $type
     * @return bool|int
     */
    public function getTermId($title, $vocabulary_id, $language_id = null, $type = 0)
    {
        $title = trim($title);
        if ($title == '') {
            return false;
        }

        if (!is_numeric($vocabulary_id)) {
            $vocabulary_id = $this->vocabulary($vocabulary_id);
        }

        $language_id = $this->getLanguage($vocabulary_id, $language_id);

        $search = $this->searchTerm($title, $vocabulary_id, $language_id);
        if ($search) {
            return $search;
        }

        // Add term
        $term = new TermContainer(['vocabulary_id' => $vocabulary_id]);

        if ($type !== 0) {
            $term->type = $type;
        }
        $term->save();

        // Add translation
        $translation = [
            'language_id' => $language_id,
            'title' => $title,
        ];
        $term->translations()->save(new TermData($translation));

        //return it
        return $term->id;
    }

    /**
     * Adds one or more tags and returns an array of id's
     *
     * @param  array $taxonomies
     * @return array
     */
    public function getTermIds($taxonomies)
    {
        $tags = [];
        foreach ($taxonomies as $voc => $terms) {
            $vocabulary_id = $this->vocabulary($voc);
            $exploded = is_array($terms)? $terms : explode(',', $terms);

            foreach ($exploded as $term) {
                $result = $this->getTermId($term, $vocabulary_id);
                if ($result) {
                    $tags[] = $result;
                }
            }
        }

        return $tags;
    }
}
