<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BorrowingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// المسارات العامة (لا تحتاج تسجيل دخول)
Route::group(['prefix' => 'v1'], function () {
    // مسارات المصادقة
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // مسارات عرض الكتب والتصنيفات العامة
    Route::get('books', [BookController::class, 'index']);
    Route::get('books/{book}', [BookController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
});

// المسارات التي تحتاج تسجيل دخول
Route::group(['prefix' => 'v1', 'middleware' => ['auth:sanctum']], function () {
    // مسارات المستخدم
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
    });

    // مسارات الكتب (للمشرفين فقط)
    Route::group(['prefix' => 'books', 'middleware' => 'admin'], function () {
        Route::post('/', [BookController::class, 'store']);
        Route::put('/{book}', [BookController::class, 'update']);
        Route::delete('/{book}', [BookController::class, 'destroy']);
    });

    // مسارات التصنيفات (للمشرفين فقط)
    Route::group(['prefix' => 'categories', 'middleware' => 'admin'], function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
        Route::patch('/{category}/toggle-status', [CategoryController::class, 'toggleStatus']);
    });

    // مسارات الاستعارة
    Route::group(['prefix' => 'borrowings'], function () {
        // مسارات للمستخدمين العاديين
        Route::get('/', [BorrowingController::class, 'index']);
        Route::post('/', [BorrowingController::class, 'store']);
        Route::get('/{borrowing}', [BorrowingController::class, 'show']);
        
        // مسارات للمشرفين فقط
        Route::group(['middleware' => 'admin'], function () {
            Route::put('/{borrowing}', [BorrowingController::class, 'update']);
            Route::delete('/{borrowing}', [BorrowingController::class, 'destroy']);
        });

        // مسار إرجاع الكتاب (متاح للجميع)
        Route::patch('/{borrowing}/return', [BorrowingController::class, 'return']);
    });
});

// مسار للتعامل مع الطلبات غير الموجودة
Route::fallback(function () {
    return response()->json([
        'status' => false,
        'message' => 'المسار غير موجود'
    ], 404);
});
