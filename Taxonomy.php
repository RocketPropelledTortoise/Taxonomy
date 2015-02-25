<?php namespace Rocket\Taxonomy;

use Rocket\Taxonomy\Model\TermContainer;
use Rocket\Taxonomy\Model\TermContent;
use Rocket\Taxonomy\Model\TermData;
use Rocket\Taxonomy\Model\Vocabulary;
use Rocket\Taxonomy\Repositories\TermRepositoryInterface as TermRep;
use Rocket\Taxonomy\Repositories\TermHierarchyRepositoryInterface as TermHieraRep;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Rocket\Translation\I18NFacade as I18N;
use DB;

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
     * @var \Illuminate\Cache\Repository
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
     * Initialize the taxonomy, Loads all existing taxonomies
     */
    public function __construct(CacheRepository $cache, TermRep $termRepository, TermHieraRep $termHierarchyRepository)
    {
        $this->termRepository = $termRepository;
        $this->termHierarchyRepository = $termHierarchyRepository;
        $this->cache = $cache;

        //get the list of taxonomies
        $vocs = $cache->remember('Rocket::Taxonomy::Vocabularies', 60 * 24 * 7, function() {
            return Vocabulary::all();
        });

        //initialize default datas
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
     * Get a term from the cache, localized
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
     * @return array|null
     */
    public function getAncestry($term_id)
    {
        return $this->termHierarchyRepository->getAncestry($term_id);
    }

    /**
     * Get the complete graph
     * @param $term_id
     * @return array|null
     */
    public function getDescent($term_id)
    {
        return $this->termHierarchyRepository->getDescent($term_id);
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
     * @param $term
     * @param  int $vocabulary_id
     * @param  int $language_id
     * @param  int $parent_id
     * @return bool|int
     */
    public function getTermId($term, $vocabulary_id, $language_id = null, $type = 0)
    {
        $term = trim($term);
        if ($term == '') {
            return false;
        }

        if (!is_numeric($vocabulary_id)) {
            $vocabulary_id = $this->vocabulary($vocabulary_id);
        }

        $language_id = $this->getLanguage($vocabulary_id, $language_id);

        $search = $this->searchTerm($term, $vocabulary_id, $language_id);
        if ($search != null) {
            return $search;
        }

        // Add term
        $terms = [
            'vocabulary_id' => $vocabulary_id,
        ];

        if ($type !== 0) {
            $terms['type'] = $type;
        }
        $term_id = TermContainer::insertGetId($terms);

        // Add translations
        $word = [
            'language_id' => $language_id,
            'term_id' => $term_id,
            'title' => $term,
        ];

        TermData::insert($word);

        //return it
        return $term_id;
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
