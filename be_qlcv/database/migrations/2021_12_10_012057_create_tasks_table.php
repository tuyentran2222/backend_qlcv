<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('taskCode');
            $table->string('taskName');
            $table->string('taskDescription');
            $table->integer('priority');
            $table->date('taskStart');
            $table->date('taskEnd')->nullable()->default(null);
            $table->integer('status')->default(1);
            $table->double('levelCompletion')->default(0);
            $table->unsignedBigInteger('taskPersonId');
            $table->unsignedBigInteger('parentId')->nullable();
            $table->unsignedBigInteger('projectId');
            $table->string('parentList')->nullable(null);
            $table->foreign('projectId')->references('id')->on('projects');
            $table->foreign('parentId')->references('id')->on('tasks');
            $table->foreign('taskPersonId')->references('id')->on('users');
            $table->foreign('ownerId')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
