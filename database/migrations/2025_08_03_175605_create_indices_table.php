<?php

use App\Models\Indice;
use App\Models\Livro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Livro::class)->constrained();
            $table->foreignIdFor(Indice::class, 'indice_pai_id')->nullable()->default(null)->constrained();
            $table->string('titulo');
            $table->integer('pagina');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indices');
    }
};
