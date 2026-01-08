<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::group(['middleware' => 'auth:api'], function () {
    // Notes
    Route::apiResource('notes', \App\Http\Controllers\NoteController::class);
    Route::put('notes/{id}/pin', [\App\Http\Controllers\NoteController::class, 'pin']);
    Route::put('notes/{id}/unpin', [\App\Http\Controllers\NoteController::class, 'unpin']);
    Route::put('notes/{id}/archive', [\App\Http\Controllers\NoteController::class, 'archive']);
    Route::put('notes/{id}/unarchive', [\App\Http\Controllers\NoteController::class, 'unarchive']);
    Route::put('notes/{id}/color', [\App\Http\Controllers\NoteController::class, 'color']);
    
    Route::get('ping', function() { return response()->json(['message' => 'pong']); });

    // Checklists
    Route::post('notes/{noteId}/checklist', [\App\Http\Controllers\ChecklistController::class, 'store']);
    Route::put('checklist/{id}', [\App\Http\Controllers\ChecklistController::class, 'update']);
    Route::delete('checklist/{id}', [\App\Http\Controllers\ChecklistController::class, 'destroy']);
    Route::put('checklist/{id}/toggle', [\App\Http\Controllers\ChecklistController::class, 'toggle']);

    // Labels
    Route::apiResource('labels', \App\Http\Controllers\LabelController::class);
    Route::post('notes/{noteId}/labels', [\App\Http\Controllers\LabelController::class, 'attachToNote']);
    Route::delete('notes/{noteId}/labels/{labelId}', [\App\Http\Controllers\LabelController::class, 'detachFromNote']);

    // Reminders
    Route::post('notes/{noteId}/reminder', [\App\Http\Controllers\ReminderController::class, 'store']);
    Route::delete('reminders/{id}', [\App\Http\Controllers\ReminderController::class, 'destroy']);

    // Sharing
    Route::post('notes/{noteId}/share', [\App\Http\Controllers\ShareController::class, 'share']);
    Route::delete('shared-notes/{id}', [\App\Http\Controllers\ShareController::class, 'unshare']);
    Route::get('notes/shared-with-me', [\App\Http\Controllers\ShareController::class, 'sharedWithMe']);

    // Images
    Route::post('notes/{noteId}/images', [\App\Http\Controllers\NoteImageController::class, 'store']);
    Route::delete('note-images/{id}', [\App\Http\Controllers\NoteImageController::class, 'destroy']);

    // Preferences
    Route::get('preferences', [\App\Http\Controllers\UserPreferenceController::class, 'show']);
    Route::put('preferences', [\App\Http\Controllers\UserPreferenceController::class, 'update']);
});
