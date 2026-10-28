<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A page of the site: its sections are the body, wrapped in wrapper_open ... wrapper_close
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('path')->unique(); // "/", "/about-us/"
            $table->string('title');
            $table->string('template')->default('sections'); // sections | blog_index
            $table->string('status')->default('published'); // published | draft | hidden (served, noindex, links go to the audit)
            $table->string('body_class')->nullable();
            $table->longText('wrapper_open')->nullable();
            $table->longText('wrapper_close')->nullable();
            $table->json('assets')->nullable(); // styles, theme_styles, late_styles, scripts, theme_scripts
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('type')->default('html'); // html | posts_grid
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('content'); // {"html": "..."}; posts_grid also {"count": 6}
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['page_id', 'sort_order']);
        });

        Schema::create('section_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('type');
            $table->unsignedInteger('sort_order');
            $table->json('content');
            $table->boolean('is_visible');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('body_class')->nullable();
            $table->json('assets')->nullable(); // as pages; "paged": the assets of page 2 and on
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable(); // media path
            $table->string('author_name')->default('Abu Bakar');
            $table->string('status')->default('draft'); // draft | published (published_at in the future = scheduled)
            $table->timestamp('published_at')->nullable();
            $table->timestamp('modified_at')->nullable(); // content last changed (article:modified_time)
            $table->string('body_class')->nullable();
            $table->json('assets')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique(); // /images/2026/10/photo.jpg (the original)
            $table->string('filename');
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('sizes')->nullable(); // [{"width":300,"height":169,"path":"..."}]
            $table->string('webp_path')->nullable();
            $table->string('original_path')->nullable(); // the file as uploaded, before WebP and resizing
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('canonical')->nullable();
            $table->string('robots')->nullable(); // null = site default
            $table->string('og_type')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_url')->nullable();
            $table->string('og_image')->nullable();
            $table->unsignedInteger('og_image_width')->nullable();
            $table->unsignedInteger('og_image_height')->nullable();
            $table->string('og_image_type')->nullable();
            $table->string('twitter_card')->nullable(); // null = site default
            $table->string('published_time')->nullable();
            $table->string('modified_time')->nullable();
            $table->string('author')->nullable();
            $table->string('prev')->nullable();
            $table->string('next')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('schema_blocks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('schemable');
            $table->string('type'); // Organization, FAQPage, graph ...
            $table->string('element_id')->nullable(); // id attribute of the script tag
            $table->string('variant')->nullable(); // paged: a category archive's page 2 and on (Page 2 of 2 ...)
            $table->longText('json');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('actor')->default('system');
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['activity_log', 'settings', 'redirects', 'schema_blocks', 'seo_meta', 'media', 'post_tag', 'category_post',
            'posts', 'tags', 'categories', 'section_revisions', 'page_sections', 'pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
