<?php
/**
 * Created by IntelliJ IDEA.
 * User: onigoetz
 * Date: 27.04.14
 * Time: 18:24
 */

namespace Rocket\Taxonomy\Repositories;

use Rocket\Translation\I18NFacade as I18N;
use Rocket\Taxonomy\Facade as T;
use Rocket\Taxonomy\Model\TermContainer;
use Rocket\Taxonomy\Model\TermData;
use Rocket\Taxonomy\Term;

class TermRepository implements TermRepositoryInterface
{

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    public function __construct(\Illuminate\Cache\CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function getTerm($term_id, $from_cache = true)
    {
        if (!$from_cache || !$data = $this->cache->get('Rocket::Taxonomy::Term::' . $term_id)) {
            $data = $this->cacheTerm($term_id);
        }

        if (!$data) {
            return false;
        }

        return new Term($data);
    }

    /**
     * Puts the term in the cache and returns it for usage
     * @param  integer $term_id
     * @return array
     */
    protected function cacheTerm($term_id)
    {
        $term_table = (new TermContainer)->getTable();
        $data_table = (new TermData)->getTable();

        $translations = TermContainer::where($term_table . '.id', $term_id)
            ->select('term_id', 'vocabulary_id', $data_table . '.id', 'language_id', 'title', 'description')
            ->join($data_table, $term_table . '.id', '=', $data_table . '.term_id')
            ->get();

        if (!count($translations)) {
            return false;
        }

        $term = array();
        foreach ($translations as $t) {
            $term[$t->language_id] = $t;
        }

        $first = current($term);

        $final_term = array(
            'word_id' => $first->id,
            'term_id' => $first->term_id,
            'parent_id' => $first->parent_id,
            'vocabulary_id' => $first->vocabulary_id,
            'content_id' => $first->content_id,
            'weight' => $first->weight,
            'type' => (bool)$first->type,
        );

        if (T::isTranslatable($first->vocabulary_id)) {
            foreach (I18N::languages() as $lang => $l) {
                if (array_key_exists($l['id'], $term)) {
                    $d = $term[$l['id']];
                } else {
                    $d = $first;
                }

                $final_term['lang_' . $lang] = array(
                    'translated' => ($l['id'] == $d->language_id) ? true : false,
                    'title' => ($l['id'] == $d->language_id) ? $d->title : '<span class="not_tagged" title="' . $d->term_id . '">' . $d->title . '</span>',
                    'description' => ($l['id'] == $d->language_id) ? $d->description : '<span class="not_tagged" title="' . $d->term_id . '">' . $d->description . '</span>'
                );
            }
        } else {
            $final_term['has_translations'] = false;
            $final_term['lang'] = array(
                'translated' => true,
                'title' => $first->title,
                'description' => $first->description
            );
        }

        $this->cache->put('Rocket::Taxonomy::Term::' . $term_id, $final_term, 60 * 0);

        return $final_term;
    }
}
