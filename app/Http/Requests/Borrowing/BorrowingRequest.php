<?php

namespace App\Http\Requests\Borrowing;

use Illuminate\Foundation\Http\FormRequest;

class BorrowingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'book_id' => 'required|exists:books,id',
            'borrowed_at' => 'required|date',
            'due_date' => 'required|date|after:borrowed_at',
            'notes' => 'nullable|string'
        ];

        // إضافة قواعد إضافية عند تحديث الاستعارة
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['returned_at'] = 'nullable|date|after:borrowed_at';
            $rules['status'] = 'required|in:pending,borrowed,returned,overdue';
            $rules['fine_amount'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'book_id.required' => 'يجب تحديد الكتاب',
            'book_id.exists' => 'الكتاب غير موجود',
            'borrowed_at.required' => 'تاريخ الاستعارة مطلوب',
            'borrowed_at.date' => 'تاريخ الاستعارة يجب أن يكون تاريخاً صحيحاً',
            'due_date.required' => 'تاريخ الإرجاع المتوقع مطلوب',
            'due_date.after' => 'تاريخ الإرجاع يجب أن يكون بعد تاريخ الاستعارة',
            'returned_at.after' => 'تاريخ الإرجاع الفعلي يجب أن يكون بعد تاريخ الاستعارة',
            'status.required' => 'حالة الاستعارة مطلوبة',
            'status.in' => 'حالة الاستعارة غير صحيحة',
            'fine_amount.numeric' => 'مبلغ الغرامة يجب أن يكون رقماً',
            'fine_amount.min' => 'مبلغ الغرامة يجب أن يكون 0 أو أكثر'
        ];
    }
}
