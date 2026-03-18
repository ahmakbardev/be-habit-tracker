<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MediaController extends Controller
{
    /**
     * Upload file to Media Platform
     */
    public function upload(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No file uploaded.'
                ], 400);
            }

            $file = $request->file('file');
            $mediaUrl = config('services.media.url');
            $projectId = config('services.media.project_id');

            // If project_id is not set, we might need to find or create it
            if (!$projectId) {
                // Try to find project by name
                $response = Http::get($mediaUrl . '/api/projects');
                if ($response->successful()) {
                    $projects = $response->json();
                    $projectName = config('services.media.project_name', 'habit-tracker');
                    
                    $found = collect($projects)->firstWhere('name', $projectName);
                    if ($found) {
                        $projectId = $found['id'];
                    } else {
                        // Create project
                        $createRes = Http::post($mediaUrl . '/api/projects', [
                            'name' => $projectName
                        ]);
                        if ($createRes->successful()) {
                            $projectId = $createRes->json()['id'];
                        }
                    }
                }
            }

            if (!$projectId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not determine media project ID.'
                ], 500);
            }

            // Proxy the upload to media platform
            $response = Http::attach(
                'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
            )->post($mediaUrl . '/api/files/upload', [
                'project_id' => $projectId,
                'is_public' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Construct the direct link as per documentation
                // http://localhost:3000/uploads/{physical_name}
                $physicalName = $data['physical_name'];
                $publicUrl = $mediaUrl . '/uploads/' . $physicalName;

                return response()->json([
                    'status' => 'success',
                    'url' => $publicUrl,
                    'data' => $data
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Media platform upload failed.',
                'error' => $response->body()
            ], $response->status());

        } catch (Exception $e) {
            Log::error('Upload Media Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload media.'
            ], 500);
        }
    }
}
