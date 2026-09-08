<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/transactions/no-trx', [ApiController::class, 'getNoTrx']);
Route::get('ticket/check', [ApiController::class, 'check']);
Route::get('ticket/{ticket}/individu-check', [ApiController::class, 'checkIndividualTicket']);
Route::get('ticket/{ticket}/group-check', [ApiController::class, 'checkGroupTicket']);
Route::get('ticket/code', [ApiController::class, 'getCode']);
Route::get('ticket/{id}/printQR', [ApiController::class, 'printQR']);
Route::get('ticket/group', [ApiController::class, 'detailGroup']);
Route::get('ticket/group-last', [ApiController::class, 'detailGroupLast']);
Route::get('/members', [MemberController::class, 'findOne']);
Route::get('/terusan', [ApiController::class, 'gateTerusan']);

Route::get("last-member", [ApiController::class, "last_member"]);
