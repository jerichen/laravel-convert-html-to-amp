<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmpArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amp_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->index()->nullable();
            $table->mediumText('content')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->datetime('online_time')->nullable()->index();
            $table->datetime('offline_time')->nullable()->index();
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('amp_articles');
    }
}
