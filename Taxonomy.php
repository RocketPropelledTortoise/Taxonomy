<?php

/**
 * Taxonomy manager
 */

namespace Rocket\Taxonomy;

use Rocket\Taxonomy\Model\TermContainer;
use Rocket\Taxonomy\Model\TermContent;
use Rocket\Taxonomy\Model\TermData;
use Rocket\Taxonomy\Model\Vocabulary;
use Rocket\Taxonomy\Repositories\TermRepositoryInterface;
use Rocket\Taxonomy\Repositories\TermHierarchyRepositoryInterface;
use I18N;

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
    public $terms = array();

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
     * @var TermRepositoryInterface
     */
    protected $termRepository;

    /**
     * @var TermHierarchyRepositoryInterface
     */
    protected $termHierarchyRepository;

    /**
     * Initialize the taxonomy, Loads all existing taxonomies
     */
    public function __construct(\Illuminate\Cache\CacheManager $cache, TermRepositoryInterface $termRepository, TermHierarchyRepositoryInterface $termHierarchyRepository)
    {
        $this->termRepository = $termRepository;
        $this->termHierarchyRepository = $termHierarchyRepository;
        $this->cache = $cache;

        //get the list of taxonomies
        if (!$vocs = $cache->get('Rocket::Taxonomy::List')) {
            $vocs = $this->cacheVocabularies();
        }

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

        return $this->vocabularyById[$vid]->translatable;
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
            return $this->vocabularyById[$key]->name;
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
     * Regenerate the list of vocabularies in the cache
     *
     * @return array
     */
    public function cacheVocabularies()
    {
        $vocs = Vocabulary::all();
        $this->cache->put('Rocket::Taxonomy::List', $vocs, 60 * 24 * 7);

        return $vocs;
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
     * Get the list of contents related to a term
     * @param  integer $term_id
     * @return array
     */
    public function getContentsByTerm($term_id)
    {
        return TermContent::where('term_id', $term_id)->lists('content_id');
    }

    /**
     * Cache all terms of a content (only id's)
     * @param  integer $content_id
     * @return array
     */
    protected function cacheTermsForContent($content_id)
    {
        $term_content = (new TermContent)->getTable();
        $terms = TermContainer::where('content_id', $content_id)
            ->join($term_content, 'id', '=', 'term_id')
            ->get(['term_id', 'vocabulary_id']);

        if (empty($terms)) {
            return array();
        }

        $results = array();
        foreach ($terms as $term) {
            $results[$term->vocabulary_id][] = $term->term_id;
        }

        $this->cache->put(
            'Rocket::Taxonomy::Content::' . $content_id,
            $results,
            60 * 24 * 7 * 0 //TODO :: remove *0
        ); //a whole week, cuz it's automatically recached

        return $results;
    }

    /**
     * Get all paths for a term
     *
     * @param int $term_id
     * @return array<array<int>>
     */
    public function getPaths($term_id)
    {
        return $this->termHierarchyRepository->getPaths($term_id);
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
     * Get the terms from a content
     *
     * @param  int $content_id
     * @param  int|string $vocabulary_id
     * @param  bool $parse_terms
     * @return array
     */
    public function getTermsForContent($content_id, $vocabulary_id, $parse_terms = true)
    {
        if (!is_numeric($vocabulary_id)) {
            $vocabulary_id = $this->vocabulary($vocabulary_id);
        }

        if (!$data = $this->cache->get('Rocket::Taxonomy::Content::' . $content_id)) {
            $data = $this->cacheTermsForContent($content_id);
        }

        $results = array();
        if (array_key_exists($vocabulary_id, $data)) {
            foreach ($data[$vocabulary_id] as $term) {
                if ($parse_terms) {
                    $results[] = $this->getTerm($term);
                } else {
                    $results[] = $term;
                }
            }
        }

        return $results;
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

                $results = array();
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
     * Search a specific term, if it doesn't exist
     *
     * @param  string $term
     * @param  int $vocabulary_id
     * @param  int $language_id
     * @param  array $exclude
     * @return mixed
     */
    public function searchTerm($term, $vocabulary_id, $language_id = null, $exclude = array())
    {
        $language_id = 1;
        if ($this->isTranslatable($vocabulary_id) && $language_id == null) {
            $l_iso = Session::get('language');
            $language_id = I18N::languages($l_iso)['id'];
        }

        $term = trim($term);

        if ($term == '') {
            return false;
        }

        $query = DB::table('words');

        if (count($exclude)) {
            $query->whereNotIn('terms.id', $exclude);
        }

        $row = $query->select('terms.id')
            ->join('terms', 'terms.id', '=', 'words.term_id')
            ->where('terms.vocabulary_id', $vocabulary_id)
            ->where('words.language_id', $language_id)
            ->where('words.text', $term)
            ->first();

        if (!empty($row)) {
            return $row->id;
        }

        return false;
    }

    /**
     * Returns the id of a term, if it doesn't exist, creates it.
     *
     * @param $term
     * @param  int $vocabulary_id
     * @param  int $language_id
     * @param  int $parent_id
     * @return bool|int|mixed
     */
    public function getTermId($term, $vocabulary_id, $language_id = 0, $parent_id = 0)
    {
        $term = trim($term);

        if ($term == '') {
            return false;
        }

        $language_id = 1;
        if ($this->isTranslatable($vocabulary_id) && $language_id === 0) {
            $l_iso = Session::get('language');
            $language_id = I18N::languages($l_iso)['id'];
        }

        $search = $this->searchTerm($term, $vocabulary_id, $language_id);

        if ($search !== false) {
            return $search;
        }

        //add term
        $terms = array(
            'vocabulary_id' => $vocabulary_id,
        );

        if ($parent_id !== 0) {
            $terms['term_id'] = $parent_id;
        }
        $term_id = DB::table('terms')->insertGetId($terms);

        //add translations
        $word = array(
            'language_id' => $language_id,
            'term_id' => $term_id,
            'text' => $term,
        );

        DB::table('words')->insert($word);

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
        $tags = array();
        foreach ($taxonomies as $voc => $terms) {

            $vocabulary_id = $this->vocabulary($voc);
            if (!is_array($terms)) {
                if (strpos($terms, ',') !== false) {
                    $exploded = explode(',', $terms);
                } else {
                    $exploded = array($terms);
                }
            } else {
                $exploded = $terms;
            }

            foreach ($exploded as $term) {

                $result = $this->getTermId($term, $vocabulary_id);
                if ($result) {
                    $tags[] = $result;
                }
            }
        }

        return $tags;
    }


    /**
     * Set the terms to a content, removes the old ones
     *
     * @param integer $content_id
     * @param array $terms
     * @param integer $vocabulary_id
     */
    public function setTermsForContent($content_id, $terms, $vocabulary_id = null)
    {
        $this->removeTermsFromContent($content_id, $vocabulary_id);

        foreach ($terms as $term_id) {
            $content = new TermContent();
            $content->content_id = $content_id;
            $content->term_id = $term_id;
            $content->save();
        }

        //recache the terms
        $this->cacheTermsForContent($content_id);
    }

    /**
     * Removes terms specified by a vocabulary, or all
     *
     * @param integer $content_id
     * @param integer $vocabulary_id
     */
    protected function removeTermsFromContent($content_id, $vocabulary_id = null)
    {
        if ($vocabulary_id == null) {
            TermContent::where('content_id', $content_id)->delete();
            return;
        }

        $results = TermContent::with('term')
            ->where('content_id', $content_id)
            ->where('vocabulary_id', $vocabulary_id)
            ->lists('id');

        if (count($results)) {
            $terms = array();
            foreach ($results as $term) {
                $terms[] = $term->id;
            }
            TermContent::whereIn('term_id', $terms)->where('content_id', $content_id)->delete();
        }
    }
}
