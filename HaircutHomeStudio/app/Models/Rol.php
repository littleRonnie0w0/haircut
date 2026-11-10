<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public $timestamps = false;

    protected $table = 'ROL';

    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'rol',
    ];

    protected function casts(): array
    {
        return [
            'rol' => 'string'
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_rol');
    }
}
