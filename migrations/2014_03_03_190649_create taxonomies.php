<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaxonomies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'taxonomy_vocabularies',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('machine_name', 255)->unique();
                $table->text('description')->nullable();
                $table->integer('hierarchy')->default(0); // 0 = disabled, 1 = single, 2 = multiple
                $table->integer('translatable')->default(1);
            }
        );

        Schema::create(
            'taxonomy_terms',
            function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('vocabulary_id');
                $table->integer('type')->default(0); // 0 = simple, 1 = category

                $table->foreign('vocabulary_id')->references('id')->on('taxonomy_vocabularies');
            }
        );

        Schema::create(
            'taxonomy_terms_data',
            function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('term_id');
                $table->unsignedInteger('language_id');
                $table->string('title', 255);
                $table->text('description')->nullable();

                $table->foreign('term_id')->references('id')->on('taxonomy_terms');
                $table->foreign('language_id')->references('id')->on('languages');
            }
        );

        Schema::create(
            'taxonomy_term_hierarchy',
            function (Blueprint $table) {
                $table->unsignedInteger('term_id');
                $table->unsignedInteger('parent_id');

                $table->foreign('term_id')->references('id')->on('taxonomy_terms');
                $table->foreign('parent_id')->references('id')->on('taxonomy_terms');

                $table->primary(['term_id', 'parent_id']);
            }
        );

        Schema::create(
            'taxonomy_content',
            function (Blueprint $table) {
                $table->unsignedInteger('term_id');
                $table->unsignedInteger('relationable_id');
                $table->string('relationable_type');

                $table->foreign('term_id')->references('id')->on('taxonomy_terms');

                $table->primary(['term_id', 'relationable_id', 'relationable_type'], 'taxonomy_contents_composed_primary');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('taxonomy_content');
        Schema::drop('taxonomy_term_hierarchy');
        Schema::drop('taxonomy_terms_data');
        Schema::drop('taxonomy_terms');
        Schema::drop('taxonomy_vocabularies');
    }
}
