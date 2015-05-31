<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(
			'tags', function (Blueprint $table) {
				$table->increments('id');
				$table->string('label');
			}
		);

		Schema::create(
			'post_tag', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('post_id');
				$table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
				$table->unsignedInteger('tag_id');
				$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
				$table->string('extra')->nullable();
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
		Schema::dropIfExists('post_tag');
		Schema::dropIfExists('tags');
	}
}
