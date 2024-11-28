<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Borrowing\BorrowingRequest;
use App\Http\Resources\BorrowingResource;
use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Borrowing::with(['user', 'book']);

            // فلترة حسب المستخدم
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // فلترة حسب الكتاب
            if ($request->book_id) {
                $query->where('book_id', $request->book_id);
            }

            // فلترة حسب الحالة
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // فلترة الكتب المتأخرة
            if ($request->boolean('overdue')) {
                $query->whereNull('returned_at')
                      ->where('due_date', '<', now());
            }

            $borrowings = $query->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc')
                               ->paginate($request->per_page ?? 10);

            return BorrowingResource::collection($borrowings);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الاستعارات',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(BorrowingRequest $request)
    {
        try {
            DB::beginTransaction();

            $book = Book::findOrFail($request->book_id);

            // التحقق من توفر الكتاب
            if (!$book->isAvailableForBorrowing()) {
                return response()->json([
                    'status' => false,
                    'message' => 'الكتاب غير متوفر للاستعارة حالياً'
                ], Response::HTTP_BAD_REQUEST);
            }

            // التحقق من عدم وجود استعارة سابقة للمستخدم لنفس الكتاب
            $existingBorrowing = Borrowing::where('user_id', auth()->id())
                                        ->where('book_id', $book->id)
                                        ->whereNull('returned_at')
                                        ->first();

            if ($existingBorrowing) {
                return response()->json([
                    'status' => false,
                    'message' => 'لديك نسخة مستعارة من هذا الكتاب بالفعل'
                ], Response::HTTP_BAD_REQUEST);
            }

            // إنشاء الاستعارة
            $borrowing = Borrowing::create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'borrowed_at' => $request->borrowed_at ?? now(),
                'due_date' => $request->due_date,
                'status' => 'borrowed',
                'notes' => $request->notes
            ]);

            // تحديث عدد النسخ المتاحة
            $book->decrement('available_copies');

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الاستعارة بنجاح',
                'data' => new BorrowingResource($borrowing->load(['user', 'book']))
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الاستعارة',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Borrowing $borrowing)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => new BorrowingResource($borrowing->load(['user', 'book']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء عرض الاستعارة',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(BorrowingRequest $request, Borrowing $borrowing)
    {
        try {
            DB::beginTransaction();

            $oldStatus = $borrowing->status;
            $borrowing->update($request->validated());

            // إذا تم إرجاع الكتاب
            if ($request->status === 'returned' && $oldStatus !== 'returned') {
                $borrowing->book->increment('available_copies');
            }
            // إذا تم إلغاء الإرجاع
            elseif ($oldStatus === 'returned' && $request->status !== 'returned') {
                $borrowing->book->decrement('available_copies');
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الاستعارة بنجاح',
                'data' => new BorrowingResource($borrowing->load(['user', 'book']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الاستعارة',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function return(Borrowing $borrowing)
    {
        try {
            DB::beginTransaction();

            if ($borrowing->returned_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'تم إرجاع هذا الكتاب مسبقاً'
                ], Response::HTTP_BAD_REQUEST);
            }

            $borrowing->update([
                'returned_at' => now(),
                'status' => 'returned'
            ]);

            // تحديث عدد النسخ المتاحة
            $borrowing->book->increment('available_copies');

            // حساب الغرامة إذا كان متأخراً
            if ($borrowing->isOverdue()) {
                $daysLate = now()->diffInDays($borrowing->due_date);
                $fineAmount = $daysLate * 1; // مثال: 1 ريال لكل يوم تأخير
                $borrowing->update(['fine_amount' => $fineAmount]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إرجاع الكتاب بنجاح',
                'data' => new BorrowingResource($borrowing->load(['user', 'book']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرجاع الكتاب',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Borrowing $borrowing)
    {
        try {
            if ($borrowing->status === 'borrowed') {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن حذف استعارة نشطة'
                ], Response::HTTP_BAD_REQUEST);
            }

            $borrowing->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف الاستعارة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الاستعارة',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
