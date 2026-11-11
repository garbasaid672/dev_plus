<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'sender_name',
        'sender_email',
        'subject',
        'body',
        'source',
        'external_id',
        'conversation_id',
        'status',
        'reply',
        'replied_at',
        'reply_error',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
            'whatsapp_sent_at' => 'datetime',
        ];
    }
}
