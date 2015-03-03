<?php namespace Rocket\Taxonomy;

use ArrayAccess;
use Rocket\Taxonomy\Exception\UndefinedLanguageException;
use Rocket\Taxonomy\Facade as T;
use Rocket\Translation\I18NFacade as I18N;

/**
 * Taxonomy term
 *
 * The terms are instantiated automatically.
 *
 * you can then use them in multiple ways
 *
 *     $term = new Term...
 *
 *     echo $term;
 *     -> will output the term in the current language
 *
 *     $term->title()
 *     $term['title']
 *     -> will return the term in the current language
 *
 *     $term->title('en')
 *     -> will output the term in English
 *
 *     $term->description()
 *     $term['description']
 *     -> will return the term's content in the current language
 *
 *     $term->description('en')
 *     -> will output the term's content in English
 *
 *     $term->translated()
 *     $term['translated']
 *     -> true if it was translated in the current language
 *
 *     $term->translated('en')
 *     -> true if it was translated in english
 *
 * @package Taxonomy
 */
class Term implements ArrayAccess
{
    /**
     * Data for the term
     *
     * @var array
     */
    private $container = array(
        'has_translations' => true,
        'type' => 0
    );

    /**
     * Create the term
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->container = array_merge($this->container, $data);
    }

    /**
     * Wake from sleep
     *
     * @param  array $data
     * @return Term
     */
    public static function __set_state($data)
    {
        return new Term($data['container']);
    }

    /**
     * Returns true if the vocabulary of the term can be translated
     *
     * @return bool
     */
    protected function hasTranslations()
    {
        return $this->container['has_translations'];
    }

    /**
     * Returns true if the term is a (sub)category
     *
     * @return bool
     */
    public function isSubcategory()
    {
        return $this->container['type'] == T::TERM_CATEGORY;
    }

    /**
     * Returns the type of the term (category or term)
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Get the term id
     *
     * @return int
     */
    public function id()
    {
        return $this->container['term_id'];
    }

    /**
     * Get the term's title
     *
     * @param string $language
     * @return string
     */
    public function title($language = '')
    {
        return $this->string('title', $language);
    }

    /**
     * Get the term's description
     *
     * @param string $language
     * @return string
     */
    public function description($language = '')
    {
        return $this->string('description', $language);
    }

    /**
     * Return the textual version of the term
     *
     * @param  string $language
     * @return string
     */
    public function string($key, $language = '')
    {
        if (!$this->hasTranslations()) {
            return $this->container['lang'][$key];
        }

        if ($language == '') {
            $language = I18N::getCurrent();
        }

        if (array_key_exists('lang_' . $language, $this->container)) {
            return $this->container['lang_' . $language][$key];
        }

        return '';
    }

    /**
     * Is it translated in this language ?
     *
     * @param string $language
     * @return bool
     */
    public function translated($language = '')
    {
        if (!$this->hasTranslations()) {
            return true;
        }

        if ($language == '') {
            $language = I18N::getCurrent();
        }

        if (array_key_exists('lang_' . $language, $this->container)) {
            return $this->container['lang_' . $language]['translated'];
        }

        return false;
    }

    public function addParent($parent_id)
    {
        //todo : check if we can do that (hierarchy type)

        return T::addParent($this->id(), $parent_id);
    }

    public function addParents(array $parents)
    {
        foreach ($parents as $parent_id) {
            $this->addParent($parent_id);
        }
    }

    public function setParent($parent_id)
    {
        T::unsetParents($this->id());

        $this->addParent($parent_id);
    }

    public function setParents(array $parents)
    {
        T::unsetParents($this->id());

        $this->addParents($parents);
    }

    /**
     * Retrieve a language for edition
     *
     * @param string $language
     * @return Model\TermData
     * @throws UndefinedLanguageException
     */
    public function editLanguage($language = '')
    {
        if (!array_key_exists('lang_' . $language, $this->container)) {
            throw new UndefinedLanguageException;
        }

        return $this->container['lang_' . $language];
    }

    /**
     * Array access
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (!is_null($offset)) {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Array Access
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Array Access
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Array access
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (!in_array($offset, ['title', 'description', 'translated'])) {
            return isset($this->container[$offset]) ? $this->container[$offset] : null;
        }

        if (!$this->hasTranslations()) {
            return $this->container['lang'][$offset];
        }

        if (array_key_exists('lang_' . I18N::getCurrent(), $this->container)) {
            return $this->container['lang_' . I18N::getCurrent()][$offset];
        }

        return null;
    }

    /**
     * echo Term - outputs the term in the current language
     *
     * @return string
     */
    public function __toString()
    {
        return $this->title();
    }
}
