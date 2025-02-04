<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateParseTimesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('tlcmap.parse_times');
        Schema::create('tlcmap.parse_times', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float('text_size');  // Size of the text (in KB)
            $table->float('parse_time'); // Time taken to parse the text (in seconds)
            $table->timestamps();
        });

        // Insert predefined parse times
        DB::table('tlcmap.parse_times')->insert([
            [
                'text_size' => 6.9189453125,
                'parse_time' => 25,           
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'text_size' => 20.58, 
                'parse_time' => 85.45, 
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('parse_times');
    }
}
