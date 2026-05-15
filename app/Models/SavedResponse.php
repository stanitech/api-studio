<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedResponse extends Model
{
    protected $fillable = [
        'user_id', 'collection_id', 'endpoint_id', 'endpoint_name',
        'method', 'url', 'status_code', 'response_body',
        'request_headers', 'request_body', 'response_time_ms', 'label',
    ];

    protected $casts = [
        'request_headers' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
