<?php

/**
 * Taxonomy manager : Terms
 */

namespace Rocket\Taxonomy;

use ArrayAccess;
use I18N;

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
 *     -> will return the term in the current language
 *
 *     $term->title('en')
 *     -> will output the term in English
 *
 *     $term->translated()
 *     -> true if it was translated in the current language
 *
 *     $term->translated('en')
 *     -> true if it was translated in english
 *
 * @todo Document ArrayAccess
 *
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
        'has_translations' => true
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
     * echo Term - outputs the term in the current language
     * @return string
     */
    public function __toString()
    {
        return $this->title();
    }

    /**
     * Wake from sleep
     *
     * @param  array $data
     * @return \Taxonomy\Term
     */
    public static function __set_state($data)
    {
        return new Term($data['container']);
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
     * @deprecated
     * @param string $language
     */
    public function text($language = '')
    {
        \Log::warning('deprectated $text');
        return $this->string('title', $language);
    }

    /**
     * @param string $language
     */
    public function title($language = '')
    {
        return $this->string('title', $language);
    }

    /**
     * @param string $language
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
        if (!$this->container['has_translations']) {
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

    public function isSubcategory()
    {
        return $this->container['type'] == 1;
    }

    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Is it translated in this language ?
     *
     * @param string $language
     * @return bool
     */
    public function translated($language = '')
    {
        if (!$this->container['has_translations']) {
            return $this->container['lang']['translate'];
        } else {
            if ($language == '') {
                $language = I18N::getCurrent();
            }

            if (array_key_exists('lang_' . $language, $this->container)) {
                return $this->container['lang_' . $language]['translated'];
            }
        }

        return false;
    }

    /**
     * Array access
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
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
        if (!in_array($offset, ['title', 'description', 'text', 'translated'])) {
            return isset($this->container[$offset]) ? $this->container[$offset] : null;
        }

        if ($offset == 'text') {
            $offset = 'title';
            \Log::warning('text is deprecated implemented');
        }

        if (!$this->container['has_translations']) {
            return $this->container['lang'][$offset];
        }

        if (array_key_exists('lang_' . I18N::getCurrent(), $this->container)) {
            return $this->container['lang_' . I18N::getCurrent()][$offset];
        }

        return null;
    }
}
