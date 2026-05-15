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
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropForeign('traslados_ibfk_3');
            $table->dropForeign('traslados_ibfk_4');
            $table->dropColumn(['origen', 'destino']);
        });

        if (!Schema::hasColumn('traslados', 'direccion_origen')) {
            Schema::table('traslados', function (Blueprint $table) {
                $table->string('direccion_origen')->nullable();
                $table->decimal('lat_origen', 10, 8)->nullable();
                $table->decimal('lng_origen', 11, 8)->nullable();
                $table->string('direccion_destino')->nullable();
                $table->decimal('lat_destino', 10, 8)->nullable();
                $table->decimal('lng_destino', 11, 8)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropColumn([
                'direccion_origen', 'lat_origen', 'lng_origen',
                'direccion_destino', 'lat_destino', 'lng_destino'
            ]);
            $table->integer('origen')->nullable();
            $table->integer('destino')->nullable();
            $table->foreign('origen')->references('id')->on('lugares')->onDelete('cascade');
            $table->foreign('destino')->references('id')->on('lugares')->onDelete('cascade');
        });
    }
};
