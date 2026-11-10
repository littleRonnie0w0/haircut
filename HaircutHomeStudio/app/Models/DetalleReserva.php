<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReserva extends Model
{
    public $timestamps = false;

    protected $table = 'DETALLE_RESERVA';

    protected $primaryKey = 'id_detalle_reserva';

    protected $fillable = ['monto_total', 'id_servicio', 'id_reserva'];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }
}
