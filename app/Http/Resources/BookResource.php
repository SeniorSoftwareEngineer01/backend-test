<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'isbn' => $this->isbn,
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            'file_path' => $this->file_path ? asset('storage/' . $this->file_path) : null,
            'categories' => CategoryResource::collection($this->categories),
            'available_copies' => $this->available_copies,
            'total_copies' => $this->total_copies,
            'publication_year' => $this->publication_year,
            'publisher' => $this->publisher,
            'pages' => $this->pages,
            'language' => $this->language,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'borrowing_status' => $this->when(auth()->check(), function () {
                $borrowing = $this->borrowings()
                    ->where('user_id', auth()->id())
                    ->whereNull('returned_at')
                    ->first();
                return $borrowing ? [
                    'status' => $borrowing->status,
                    'due_date' => $borrowing->due_date->format('Y-m-d'),
                    'is_overdue' => $borrowing->isOverdue()
                ] : null;
            })
        ];
    }
}
