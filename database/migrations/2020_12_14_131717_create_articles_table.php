<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
			$table->integer('site_id')->nullable();
			$table->text('title')->nullable();
			$table->text('description')->nullable();
			$table->longText('text')->nullable();
			$table->string('url');
			$table->string('image_url')->nullable();
			$table->string('category')->nullable();
			$table->timestamp('published_at')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
