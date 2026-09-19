<?php

declare(strict_types=1);

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';

    protected $table = 'oauth_clients';

    /**
     * Свойство, а не метод casts(): метод появился только в Laravel 11,
     * в Laravel 10 он игнорируется и массивы уезжают в БД как "Array".
     * Свойство работает во всех поддерживаемых версиях.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grant_types' => 'array',
        'redirect_uris' => 'array',
        'scopes' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
}
