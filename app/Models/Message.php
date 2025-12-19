<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'content',
        'attachment_url',
        'attachment_type',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    // ========== RELATIONS ==========
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ========== MÉTHODES UTILES ==========
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    public function isFromCustomer()
    {
        return $this->sender_type === 'customer';
    }

    public function isFromMerchant()
    {
        return $this->sender_type === 'merchant';
    }

    // ========== ACCESSORS ==========
    public function getAttachmentUrlAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }
}