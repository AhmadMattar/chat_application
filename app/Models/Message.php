<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'body' => 'json'
    ];
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    //user who send the message
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->withDefault([
                'name' => __('User'),
            ]);
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recipients')
            ->withPivot([
                'read_at', 'deleted_at',
            ]);
    }
}
