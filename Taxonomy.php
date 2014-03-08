<?php

/**
 * Taxonomy manager
 */

namespace Rocket\Taxonomy;

use Rocket\Taxonomy\Models\Vocabulary;
use Rocket\Taxonomy\Models\Term as TermModel;
use Rocket\Taxonomy\Models\Word;
use Cache;
use DB;
use I18N;
use Session;
use Rocket\Utilities\ParentChildTree;

/**
 * Class Taxonomy
 * @package Taxonomy
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
     * Initialize the taxonomy, Loads all existing taxonomies
     */
    public function __construct()
    {
        //get the list of taxonomies
        if (!$vocs = Cache::get('Rocket::Taxonomy::List')) {
            $vocs = $this->cacheVocabularies();
        }

        //initialize default datas
        foreach ($vocs as $v) {
            $this->vocabularyByName[$v->name] = $this->vocabularyById[$v->id] = $v;
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
        Cache::put('Rocket::Taxonomy::List', $vocs, 60 * 24 * 7);

        return $vocs;
    }

    /**
     * Puts the term in the cache and returns it for usage
     * @param  integer $term_id
     * @return array
     */
    public function cacheTerm($term_id)
    {
        $term_table = with(new TermModel)->getTable();
        $data_table = with(new TermData)->getTable();

        $translations = TermModel::where($term_table . '.id', $term_id)
            ->select(
                $term_table . '.id as term_id',
                $term_table . '.term_id as parent_id',
                $term_table . '.vocabulary_id',
                $term_table . '.content_id',
                $term_table . '.weight',
                $term_table . '.subcat',
                $data_table . '.id',
                $data_table . '.language_id',
                $data_table . '.text'
            )
            ->join('words', $term_table . '.id', '=', $data_table . '.term_id')->get();

        if (!count($translations)) {
            return false;
        }

        $term = array();
        foreach ($translations as $t) {
            $term[$t->language_id] = $t;
        }

        $first = current($term);

        $final_term = new Term(
            array(
                'word_id' => $first->id,
                'term_id' => $first->term_id,
                'parent_id' => $first->parent_id,
                'vocabulary_id' => $first->vocabulary_id,
                'content_id' => $first->content_id,
                'weight' => $first->weight,
                'subcat' => (bool)$first->subcat,
            )
        );

        if ($this->isTranslatable($first->vocabulary_id)) {
            foreach (I18N::languages() as $lang => $l) {
                if (array_key_exists($l['id'], $term)) {
                    $d = $term[$l['id']];
                } else {
                    $d = $first;
                }

                $final_term['lang_' . $lang] = array(
                    'translated' => ($l['id'] == $d->language_id) ? true : false,
                    'text' => ($l['id'] == $d->language_id) ? $d->text : '<span class="not_tagged" title="' . $d->term_id . '">' . $d->text . '</span>'
                );
            }
        } else {
            $final_term['has_translations'] = false;
            $final_term['lang'] = array(
                'translated' => true,
                'text' => $first->text
            );
        }

        Cache::put('Rocket::Taxonomy::Term::' . $term_id, $final_term, 60);

        return $final_term;
    }

    /**
     * Get a term from the cache, localized
     *
     * @param  integer $term_id
     * @return Term
     */
    public function getTerm($term_id)
    {
        if (array_key_exists($term_id, $this->terms)) {
            $data = $this->terms[$term_id];
        } else {
            //TODO :: implement administration part
            /*global $OUT;
            if ($OUT->enable_translate_administration) {
                $data = $this->cacheTerm($term_id);
                $this->terms[$term_id] = $data;

                $this->admin_taxonomy[$data['vocabulary_id']][] = $data;
            } else {*/
            if (!$data = Cache::get('Rocket::Taxonomy::Term::' . $term_id)) {
                $data = $this->cacheTerm($term_id);
                $this->terms[$term_id] = $data;
            }
            //}
        }

        if ($data === false) {
            return false;
        }

        return $data;
    }

    /**
     * Get the list of contents of a term
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
        $term_content = with(new TermContent)->getTable();
        $terms = TermModel::select('terms.id', 'vocabulary_id')
            ->where('content_id', $content_id)
            ->join($term_content, $term_content . '.term_id', '=', 'terms.id')
            ->get();

        if (empty($terms)) {
            return array();
        }

        $results = array();
        foreach ($terms as $term) {
            $results[$term->vocabulary_id][] = $term->id;
        }
        Cache::put(
            'Rocket::Taxonomy::Content::' . $content_id,
            $results,
            60 * 24 * 7
        ); //a whole week, cuz it's automatically recached

        return $results;
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

        if (!$data = Cache::get('Rocket::Taxonomy::Content::' . $content_id)) {
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
        return Cache::remember(
            'Rocket::Taxonomy::Terms::' . $vocabulary_id,
            60,
            function () use ($vocabulary_id) {
                $terms = TermModel::where('vocabulary_id', $vocabulary_id)->get(['id']);

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
}
