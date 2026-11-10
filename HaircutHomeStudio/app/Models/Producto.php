<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id_producto';

    protected $table = 'PRODUCTO';

    protected $fillable = ['producto', 'descripcion', 'precio', 'imagen', 'disponibilidad', 'id_categoria'];

    protected $appends = ['id', 'nombre', 'activo', 'categoria_id', 'orden'];

    protected function casts(): array
    {
        return [
            'disponibilidad' => 'boolean',
            'precio' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    protected function id(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->id_producto);
    }

    protected function nombre(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->producto,
            set: fn (string $valor): array => ['producto' => $valor],
        );
    }

    protected function activo(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->disponibilidad,
            set: fn (bool $valor): array => ['disponibilidad' => $valor],
        );
    }

    protected function categoriaId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->id_categoria !== null ? (int) $this->id_categoria : null,
            set: fn (int $valor): array => ['id_categoria' => $valor],
        );
    }

    /** Alias de UI: el esquema no tiene columna orden; se usa el id. */
    protected function orden(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->id_producto);
    }
}
