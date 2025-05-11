<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель для хранения токенов авторизации Zoho CRM.
 *
 * Хранит access_token, refresh_token и время истечения токена.
 *
 * @property string $access_token Токен доступа для API Zoho
 * @property string $refresh_token Токен для обновления access_token
 * @property \Carbon\Carbon $expires_at Дата и время истечения токена доступа
 * @property \Carbon\Carbon $created_at Дата и время создания записи
 * @property \Carbon\Carbon $updated_at Дата и время последнего обновления записи
 */
class ZohoToken extends Model
{
    use HasFactory;

    /**
     * Attributes that can be mass-assigned.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * Attributes that should be cast to specific types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
