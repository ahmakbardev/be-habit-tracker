<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Support\Facades\Log;
use Exception;

class PublicNoteController extends Controller
{
    public function show($token)
    {
        try {
            $note = Note::where('public_token', $token)
                ->where('is_public', true)
                ->first();

            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Page not found or no longer public.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => (string) $note->_id,
                    'title' => $note->title,
                    'content' => $note->content,
                    'plain_text_preview' => $note->plain_text_preview,
                    'updated_at' => $note->updated_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Public Note Show Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load page.'
            ], 500);
        }
    }
}
