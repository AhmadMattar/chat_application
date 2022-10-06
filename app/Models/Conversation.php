<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participants')
            ->withPivot([
                'joined_at', 'role',
            ]);
    }
    public function recipients()
    {
        return $this->hasManyThrough(
            Recipient::class,
            Message::class,
            'conversation_id',
            'message_id',
            'id',
            'id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    //user who create the conversation
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id', 'id')
            ->withDefault();
    }

}
