<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indice extends Model
{
    public $timestamps = false;

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }

    public function indicePai(): BelongsTo
    {
        return $this->belongsTo(Indice::class, 'indice_pai_id');
    }

    public function subindices(): HasMany
    {
        return $this->hasMany(Indice::class, 'indice_pai_id');
    }

    public function subindicesRecursivos(): HasMany
    {
        return $this->subindices()->with('subindicesRecursivos');
    }

    public function getIndicesPaisAttribute()
    {
        $indices = collect([$this]);
        $pai = $this->indicePai;

        while ($pai) {
            $indices->push($pai);
            $pai = $pai->indicePai;
        }

        return $indices->reverse()->values();
    }

}
