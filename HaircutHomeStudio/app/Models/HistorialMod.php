<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialMod extends Model
{
    public $timestamps = false;

    protected $table = 'HISTORIAL_MOD';

    protected $primaryKey = 'id_historial_mod';

    protected $fillable = ['detalle_modificacion', 'id_usuario', 'Nombre_usuario'];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
