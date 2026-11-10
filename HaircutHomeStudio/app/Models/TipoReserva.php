<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoReserva extends Model
{
    public $timestamps = false;

    protected $table = 'TIPO_RESERVA';

    protected $primaryKey = 'id_tipo_reserva';

    protected $fillable = ['tipo_reserva'];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_tipo_reserva');
    }
}
