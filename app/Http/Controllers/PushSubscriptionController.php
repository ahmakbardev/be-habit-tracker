<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Update/Store push subscription for the user
     */
    public function update(Request $request)
    {
        try {
            $this->validate($request, [
                'endpoint' => 'required',
                'keys.auth' => 'required',
                'keys.p256dh' => 'required'
            ]);

            $endpoint = $request->endpoint;
            $key = $request->keys['p256dh'];
            $token = $request->keys['auth'];
            $contentEncoding = $request->get('content_encoding', 'aesgcm'); // Default encoding

            // Get user (Temporary first user for development)
            $user = \App\Models\User::first();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            // Using the trait's method updatePushSubscription
            $user->updatePushSubscription($endpoint, $key, $token, $contentEncoding);

            return response()->json([
                'status' => 'success',
                'message' => 'Push subscription updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Push Subscription Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update push subscription.'
            ], 500);
        }
    }

    /**
     * Delete push subscription
     */
    public function destroy(Request $request)
    {
        try {
            $this->validate($request, [
                'endpoint' => 'required'
            ]);

            $user = \App\Models\User::first();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $user->deletePushSubscription($request->endpoint);

            return response()->json([
                'status' => 'success',
                'message' => 'Push subscription deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Push Subscription Delete Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete push subscription.'
            ], 500);
        }
    }
}
