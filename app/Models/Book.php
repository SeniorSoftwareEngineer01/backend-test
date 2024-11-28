<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'author',
        'description',
        'isbn',
        'cover_image',
        'file_path',
        'category',
        'available_copies',
        'total_copies',
        'publication_year',
        'publisher',
        'pages',
        'language',
        'is_available'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'publication_year' => 'integer',
        'pages' => 'integer',
        'available_copies' => 'integer',
        'total_copies' => 'integer'
    ];

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function isAvailableForBorrowing()
    {
        return $this->is_available && $this->available_copies > 0;
    }
}
