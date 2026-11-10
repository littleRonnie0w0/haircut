<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReserva extends Model
{
    public $timestamps = false;

    protected $table = 'ESTADO_RESERVA';

    protected $primaryKey = 'id_estado_reserva';

    protected $fillable = ['estado_reserva'];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_estado_reserva');
    }
}
