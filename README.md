# Taxonomy Component

[![Latest Version](https://img.shields.io/github/release/RocketPropelledTortoise/Core.svg?style=flat-square)](https://github.com/RocketPropelledTortoise/Core/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/RocketPropelledTortoise/Core/blob/master/LICENSE.md)
[![Build Status](https://img.shields.io/travis/RocketPropelledTortoise/Core/master.svg?style=flat-square)](https://travis-ci.org/RocketPropelledTortoise/Core)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/RocketPropelledTortoise/Core.svg?style=flat-square)](https://scrutinizer-ci.com/g/RocketPropelledTortoise/Core/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/RocketPropelledTortoise/Core.svg?style=flat-square)](https://scrutinizer-ci.com/g/RocketPropelledTortoise/Core)
[![Total Downloads](https://img.shields.io/packagist/dt/rocket/core.svg?style=flat-square)](https://packagist.org/packages/rocket/core)

__This is a subtree split of RocketPropelledTortoise CMS - Core. Don't send pull requests here__

## What is it ?

Taxonomy is the art of classifying things. The Taxonomy Component is here to help you classify your content.

Create as many vocabularies and terms that you want and assign them to content.

Vocabularies can be Regions, Countries, Tags, Categories.<br />
A vocabulary contains terms, each term can have one or more sub-terms.

Taxonomy is a __Laravel 5__ module

## Example

```php
use Taxonomy;
use Model;

use Rocket\Taxonomy\TaxonomyTrait;
use Rocket\Translation\Model\Language;
use Schema;

class Post extends Model {

    // add the taxonomy trait
    use TaxonomyTrait;

    public $fillable = ['content'];
}

Vocabulary::insert(['name' => 'Tag', 'machine_name' => 'tag', 'hierarchy' => 0, 'translatable' => true]);

// create the post
$post = new Post(['content' => 'a test post']);
$post->save();

// add the tags to it
$ids = T::getTermIds(['tag' => ['TDD', 'PHP', 'Add some tags']]);
$post->setTerms($ids);

// get the tags from the Post
$terms = $post->getTerms('tag')

```

## Installing

Install with composer : `composer require rocket/taxonomy`

### Service Provider

You need to add both the Taxonomy and Translation Service Providers

    '\Rocket\Translation\TranslationServiceProvider',
    '\Rocket\Taxonomy\ServiceProvider'

### Aliases

    'I18N' => '\Rocket\Translation\I18NFacade',
    'Taxonomy' => '\Illuminate\Support\Facades\Facade',

### Migrations

    php artisan migrate --path Translation/migrations
    php artisan migrate --path Taxonomy/migrations
