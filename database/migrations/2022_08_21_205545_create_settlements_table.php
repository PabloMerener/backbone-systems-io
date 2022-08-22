<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained();
            $table->foreignId('type_id')->references('id')->on('settlement_types');
            $table->smallInteger('key');
            $table->string('name', 100);
            $table->enum('zone_type', ['URBANO', 'RURAL', 'SEMIURBANO']);
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
        Schema::dropIfExists('settlements');
    }
};
