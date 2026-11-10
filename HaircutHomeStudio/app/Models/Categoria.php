<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    public $timestamps = false;

    protected $table = 'CATEGORIA';

    protected $primaryKey = 'id_categoria';

    protected $fillable = ['categoria'];

    protected $appends = ['id', 'nombre'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria');
    }

    protected function id(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->id_categoria);
    }

    protected function nombre(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->categoria);
    }
}
