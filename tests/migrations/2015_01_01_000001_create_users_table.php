<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(
			'users', function (Blueprint $table) {
				$table->increments('id');
				$table->string('username')->nullable();
				$table->string('name')->nullable();
				$table->integer('hands')->nullable();
				$table->integer('times_captured')->nullable();
				$table->string('occupation')->nullable();
				$table->timestamps();
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
		Schema::dropIfExists('users');
	}
}
