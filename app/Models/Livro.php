<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livro extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function usuarioPublicador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_publicador_id');
    }
}
