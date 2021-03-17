<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->nullable()->constrained('folders');
            $table->string('path');
            $table->string('name');
            $table->string('extension');
            $table->string('size');
            $table->string('mime_type');
            $table->enum('folder_trashed', ['Y', 'N'])->default('N');
            $table->enum('is_public', ['Y', 'N'])->default('N');
            $table->softDeletes();
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
        Schema::dropIfExists('files');
    }
}
