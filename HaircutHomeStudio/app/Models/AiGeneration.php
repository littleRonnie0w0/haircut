<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $table = 'AI_GENERATIONS';

    protected $primaryKey = 'id_ai_gen';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'style_label',
        'style_category',
        'preset',
        'input_path',
        'output_path',
        'input_hash',
        'status',
        'provider_request_id',
        'error_code',
        'ip_hash',
        'completed_at',
        'expires_at',
    ];

    protected $hidden = [
        'input_path',
        'output_path',
        'input_hash',
        'ip_hash',
        'provider_request_id',
    ];

    protected $appends = ['id'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected function id(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->id_ai_gen);
    }

    public function getRouteKeyName(): string
    {
        return 'id_ai_gen';
    }

    public static function ownerHash(User $usuario): string
    {
        return hash_hmac('sha256', 'user:'.$usuario->getKey(), (string) config('app.key'));
    }

    public function scopeDelUsuario(Builder $query, User $usuario): Builder
    {
        return $query->where('ip_hash', self::ownerHash($usuario));
    }

    public function perteneceA(User $usuario): bool
    {
        return hash_equals($this->ip_hash, self::ownerHash($usuario));
    }

    public function estaLista(): bool
    {
        return $this->status === 'completed' && $this->output_path !== null;
    }
}
