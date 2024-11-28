<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'required|string',
            'isbn' => 'nullable|string|unique:books,isbn,' . ($this->book ? $this->book->id : ''),
            'cover_image' => $this->isMethod('POST') ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'file_path' => $this->isMethod('POST') ? 'required|mimes:pdf|max:10240' : 'nullable|mimes:pdf|max:10240',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'available_copies' => 'required|integer|min:0',
            'total_copies' => 'required|integer|min:1',
            'publication_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'publisher' => 'nullable|string|max:255',
            'pages' => 'nullable|integer|min:1',
            'language' => 'required|string|max:50',
            'is_available' => 'boolean'
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'عنوان الكتاب مطلوب',
            'author.required' => 'اسم المؤلف مطلوب',
            'description.required' => 'وصف الكتاب مطلوب',
            'cover_image.required' => 'صورة الغلاف مطلوبة',
            'cover_image.image' => 'يجب أن يكون الملف صورة',
            'file_path.required' => 'ملف الكتاب PDF مطلوب',
            'file_path.mimes' => 'يجب أن يكون الملف بصيغة PDF',
            'category_ids.required' => 'يجب اختيار تصنيف واحد على الأقل',
            'available_copies.required' => 'عدد النسخ المتاحة مطلوب',
            'total_copies.required' => 'إجمالي عدد النسخ مطلوب',
        ];
    }
}
