<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Book::with('categories');

            // البحث
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%{$request->search}%")
                        ->orWhere('author', 'like', "%{$request->search}%")
                        ->orWhere('isbn', 'like', "%{$request->search}%");
                });
            }

            // التصفية حسب التصنيف
            if ($request->category_id) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            // التصفية حسب اللغة
            if ($request->language) {
                $query->where('language', $request->language);
            }

            // الترتيب
            $query->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

            $books = $query->paginate($request->per_page ?? 10);

            return BookResource::collection($books);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الكتب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(BookRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // معالجة صورة الغلاف
            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
            }

            // معالجة ملف الكتاب
            if ($request->hasFile('file_path')) {
                $data['file_path'] = $request->file('file_path')->store('books/files', 'public');
            }

            $book = Book::create($data);

            // إضافة التصنيفات
            $book->categories()->attach($request->category_ids);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إضافة الكتاب بنجاح',
                'data' => new BookResource($book)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إضافة الكتاب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Book $book)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => new BookResource($book->load('categories'))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء عرض الكتاب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(BookRequest $request, Book $book)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // معالجة صورة الغلاف
            if ($request->hasFile('cover_image')) {
                // حذف الصورة القديمة
                if ($book->cover_image) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                $data['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
            }

            // معالجة ملف الكتاب
            if ($request->hasFile('file_path')) {
                // حذف الملف القديم
                if ($book->file_path) {
                    Storage::disk('public')->delete($book->file_path);
                }
                $data['file_path'] = $request->file('file_path')->store('books/files', 'public');
            }

            $book->update($data);

            // تحديث التصنيفات
            $book->categories()->sync($request->category_ids);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الكتاب بنجاح',
                'data' => new BookResource($book)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الكتاب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Book $book)
    {
        try {
            // حذف الملفات المرتبطة
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
            }

            $book->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف الكتاب بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الكتاب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
