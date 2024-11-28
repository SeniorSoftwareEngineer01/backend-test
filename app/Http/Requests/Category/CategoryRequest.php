<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . ($this->category ? $this->category->id : ''),
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.max' => 'اسم التصنيف يجب أن لا يتجاوز 255 حرف',
            'slug.unique' => 'هذا المعرف مستخدم مسبقاً',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ'
        ];
    }
}
