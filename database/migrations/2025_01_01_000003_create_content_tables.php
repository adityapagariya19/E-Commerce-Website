<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $t) {
            $t->id();
            $t->string('eyebrow')->nullable();       // "Exclusively from JB Optician"
            $t->string('kicker')->nullable();        // "WE'RE PROUD TO INTRODUCE"
            $t->string('heading');                   // "JB Optician a online store"
            $t->string('subheading')->nullable();
            $t->string('image');
            $t->string('mobile_image')->nullable();
            $t->string('alt')->nullable();
            $t->string('cta_label')->nullable();
            $t->string('cta_url')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // One flexible table for collage tiles, promo banner, trust row and social tiles.
        Schema::create('home_blocks', function (Blueprint $t) {
            $t->id();
            $t->string('section', 30)->index();   // collage | promo | trust | social
            $t->string('eyebrow')->nullable();
            $t->string('title')->nullable();
            $t->text('subtitle')->nullable();
            $t->string('image')->nullable();
            $t->string('alt')->nullable();
            $t->string('cta_label')->nullable();
            $t->string('url')->nullable();
            $t->string('icon', 30)->nullable();   // trust row icon key
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_placeholder')->default(false); // true => seeded stand-in, not the original asset/claim
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->boolean('is_secret')->default(false); // value stored encrypted
            $t->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->text('message');
            $t->string('source', 20)->default('contact'); // contact | help_feedback
            $t->string('ip', 45)->nullable();
            $t->string('status', 20)->default('new');     // new | read | replied
            $t->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('phone', 30)->nullable();
            $t->string('source', 30)->default('footer');
            $t->string('status', 20)->default('subscribed'); // subscribed | unsubscribed
            $t->string('token', 40)->unique();
            $t->boolean('synced_omnisend')->default(false);
            $t->timestamp('subscribed_at')->nullable();
            $t->timestamp('unsubscribed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('faq_topics', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('faq_topic_id')->constrained()->cascadeOnDelete();
            $t->string('question');
            $t->text('answer')->nullable(); // null => answer not yet confirmed by the owner (shown as "contact us")
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->longText('body')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 80)->index();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'pages', 'faqs', 'faq_topics', 'newsletter_subscribers', 'contact_messages', 'settings', 'home_blocks', 'hero_slides'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
