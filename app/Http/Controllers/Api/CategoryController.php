<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // البحث
            if ($request->search) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            }

            // فلترة حسب الحالة
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // إضافة عدد الكتب لكل تصنيف
            if ($request->with_books_count) {
                $query->withCount('books');
            }

            // تحميل الكتب إذا طلب
            if ($request->with_books) {
                $query->with('books');
            }

            $categories = $query->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc')
                              ->paginate($request->per_page ?? 10);

            return CategoryResource::collection($categories);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب التصنيفات',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CategoryRequest $request)
    {
        try {
            $data = $request->validated();
            
            // إنشاء slug إذا لم يتم توفيره
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category = Category::create($data);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء التصنيف بنجاح',
                'data' => new CategoryResource($category)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء التصنيف',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Category $category)
    {
        try {
            // تحميل الكتب مع التصنيف
            $category->load('books');
            
            return response()->json([
                'status' => true,
                'data' => new CategoryResource($category)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء عرض التصنيف',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(CategoryRequest $request, Category $category)
    {
        try {
            $data = $request->validated();
            
            // تحديث slug إذا تم تغيير الاسم ولم يتم توفير slug
            if (!isset($data['slug']) && isset($data['name']) && $data['name'] !== $category->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث التصنيف بنجاح',
                'data' => new CategoryResource($category)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث التصنيف',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Category $category)
    {
        try {
            // التحقق من وجود كتب مرتبطة
            if ($category->books()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن حذف التصنيف لوجود كتب مرتبطة به'
                ], Response::HTTP_BAD_REQUEST);
            }

            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف التصنيف بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف التصنيف',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleStatus(Category $category)
    {
        try {
            $category->update([
                'is_active' => !$category->is_active
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تغيير حالة التصنيف بنجاح',
                'data' => new CategoryResource($category)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة التصنيف',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
