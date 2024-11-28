<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'book' => new BookResource($this->whenLoaded('book')),
            'borrowed_at' => $this->borrowed_at->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date->format('Y-m-d H:i:s'),
            'returned_at' => $this->returned_at ? $this->returned_at->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'is_overdue' => $this->isOverdue(),
            'fine_amount' => $this->fine_amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
