<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfileTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(
			'profiles', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->enum(
				'favorite_cheese', [
				'brie',
				'pepper jack',
				'Gouda',
				'Cheddar',
				'Provolone',
			]);
			$table->string('favorite_fruit')->nullable();
			$table->boolean('is_human')->default(false);
			$table->string('not_fillable')->nullable();
			$table->string('not_filterable')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('profiles');
	}
}
