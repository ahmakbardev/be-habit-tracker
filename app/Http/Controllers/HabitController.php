<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;

class HabitController extends Controller
{
    /**
     * Get all habits for a user with today's completion status
     */
    public function index(Request $request)
    {
        try {
            $user = User::first();
            $date = $request->query('date', Carbon::today()->toDateString());

            $habits = Habit::where('user_id', $user->id)->get();

            // Fetch completions for the selected date
            $completions = HabitCompletion::where('user_id', $user->id)
                ->where('date', $date)
                ->get()
                ->groupBy('habit_id');

            foreach ($habits as $habit) {
                $habitCompletions = $completions->get($habit->id) ?? collect();
                $habit->setRelation('today_completions', $habitCompletions);
            }

            return response()->json([
                'status' => 'success',
                'data' => $habits
            ]);
        } catch (Exception $e) {
            Log::error('Habits Index Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch habits.'
            ], 500);
        }
    }

    /**
     * Store a new habit
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon_type' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:20',
                'schedules' => 'nullable|array',
                'goal' => 'nullable|integer',
            ]);

            $user = User::first();
            $habit = Habit::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'icon_type' => $validated['icon_type'] ?? 'activity',
                'color' => $validated['color'] ?? '#4F46E5',
                'schedules' => $validated['schedules'] ?? [],
                'goal' => $validated['goal'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Habit created successfully',
                'data' => $habit
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Habit Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not save habit.'
            ], 500);
        }
    }

    /**
     * Toggle habit completion status
     */
    public function toggleLog(Request $request)
    {
        try {
            $validated = $request->validate([
                'habit_id' => 'required|string',
                'date' => 'required|date_format:Y-m-d',
                'time_slot' => 'required|string',
            ]);

            $user = User::first();
            
            $log = HabitCompletion::where('habit_id', $validated['habit_id'])
                ->where('user_id', $user->id)
                ->where('date', $validated['date'])
                ->where('time_slot', $validated['time_slot'])
                ->first();

            if ($log) {
                $log->delete();
                $status = 0;
            } else {
                HabitCompletion::create([
                    'habit_id' => $validated['habit_id'],
                    'user_id' => $user->id,
                    'date' => $validated['date'],
                    'time_slot' => $validated['time_slot'],
                    'status' => 1,
                ]);
                $status = 1;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Habit status toggled',
                'completed' => $status == 1
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Toggle Habit Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle habit.'
            ], 500);
        }
    }

    /**
     * Get efficiency percentage for a habit
     */
    public function getEfficiency($id)
    {
        try {
            $habit = Habit::find($id);
            if (!$habit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Habit not found.'
                ], 404);
            }

            // Simple logic: total completed slots / total expected slots in last 30 days
            $startDate = Carbon::today()->subDays(30);
            $totalCompleted = HabitCompletion::where('habit_id', $id)
                ->where('date', '>=', $startDate->toDateString())
                ->count();

            // Calculate expected slots: days * goal
            $daysCount = 30; 
            $expectedSlots = $daysCount * ($habit->goal ?? 1);
            
            $efficiency = ($expectedSlots > 0) ? round(($totalCompleted / $expectedSlots) * 100, 2) : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'habit_id' => $id,
                    'efficiency_percentage' => min($efficiency, 100) // Cap at 100
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Habit Efficiency Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate efficiency.'
            ], 500);
        }
    }

    /**
     * Delete a habit
     */
    public function destroy($id)
    {
        try {
            $habit = Habit::find($id);
            if (!$habit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Habit not found.'
                ], 404);
            }

            // Delete associated completions
            HabitCompletion::where('habit_id', $id)->delete();
            $habit->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Habit deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete habit.'
            ], 500);
        }
    }
}
