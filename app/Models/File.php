<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'file_path',
        'category_id',
        'user_id',
        'size',
        'folder_id',

        // field untuk workflow approval
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Relasi ke user yang menyetujui (approver).
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessor: kembalikan size dalam MB dengan 2 desimal jika ada
    public function getSizeAttribute($value)
    {
        if (is_null($value)) return null;

        // diasumsikan disimpan dalam byte
        if ($value >= 1048576) {
            return number_format($value / 1048576, 2) . ' MB';
        }

        return number_format($value / 1024, 2) . ' KB';
    }

    public function favorites()
    {
        return $this->hasMany(\App\Models\Favorite::class);
    }

    public function isFavoritedByAuth(): bool
    {
        $uid = auth()->id();
        if (!$uid) return false;

        if ($this->relationLoaded('favorites')) {
            return $this->favorites->where('user_id', $uid)->isNotEmpty();
        }

        return \App\Models\Favorite::where('user_id', $uid)
            ->where('file_id', $this->id)
            ->exists();
    }
}
