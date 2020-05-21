<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('tenants', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('name')->unique()->nullable();
			$table->string('email')->index()->nullable();
			$table->string('subdomain')->unique()->nullable();
			$table->string('connection');
			$table->boolean('is_active')->default(true);
			// $table->string('alias_domain')->unique()->nullable();
			// $table->text('meta');

			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('tenants');
	}
}
