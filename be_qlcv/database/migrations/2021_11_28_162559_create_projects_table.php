<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('projectCode');
            $table->string('projectName');
            $table->date('projectStart',$precision = 0);
            $table->date('projectEnd', $precision = 0);
            $table->string('partner');
            $table->integer('status');
            $table->unsignedBigInteger('projectId');
            $table->unsignedBigInteger('ownerId');
            $table->foreign('ownerId')
            ->references('id')
            ->on('users');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
