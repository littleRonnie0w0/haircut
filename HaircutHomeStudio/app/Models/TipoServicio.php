<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoServicio extends Model
{
    public $timestamps = false;

    protected $table = 'TIPO_SERVICIO';

    protected $primaryKey = 'id_tipo_servicio';

    protected $fillable = ['tipo_servicio', 'slug', 'imagen'];

    protected $appends = ['id', 'nombre'];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tipo_servicio');
    }

    protected function id(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->id_tipo_servicio);
    }

    protected function nombre(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->tipo_servicio);
    }
}
