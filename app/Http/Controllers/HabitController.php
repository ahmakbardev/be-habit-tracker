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
     * Logic: Shows habits created <= requested date AND (not archived OR archived > requested date)
     */
    public function index(Request $request)
    {
        try {
            $user = User::first();
            $dateStr = $request->query('date', Carbon::today()->toDateString());
            $requestDate = Carbon::parse($dateStr)->startOfDay();

            $habits = Habit::where('user_id', $user->id)
                ->where('created_at', '<=', $requestDate->endOfDay())
                ->where(function ($query) use ($requestDate) {
                    $query->whereNull('archived_at')
                          ->orWhere('archived_at', '>', $requestDate->endOfDay());
                })
                ->get();

            // Fetch completions for the selected date
            $completions = HabitCompletion::where('user_id', $user->id)
                ->where('date', $dateStr)
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
     * Get completions for a date range
     */
    public function getCompletions(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
            ]);

            $user = User::first();
            $completions = HabitCompletion::where('user_id', $user->id)
                ->whereBetween('date', [$request->start_date, $request->end_date])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $completions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch completions.'
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
     * Update an existing habit
     */
    public function update(Request $request, $id)
    {
        try {
            $habit = Habit::find($id);
            if (!$habit) {
                return response()->json(['status' => 'error', 'message' => 'Habit not found.'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'icon_type' => 'sometimes|string|max:50',
                'color' => 'sometimes|string|max:20',
                'schedules' => 'sometimes|array',
                'goal' => 'sometimes|integer',
            ]);

            $habit->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Habit updated successfully',
                'data' => $habit
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update habit.'
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

            $habit = Habit::find($validated['habit_id']);
            if (!$habit) {
                return response()->json(['status' => 'error', 'message' => 'Habit not found.'], 404);
            }

            // Validation: Prevent future dates
            if (Carbon::parse($validated['date'])->isFuture()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot log habits for future dates.'
                ], 422);
            }

            // Validation: Prevent logging before habit creation
            if (Carbon::parse($validated['date'])->lt(Carbon::parse($habit->created_at)->startOfDay())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot log habits before they were created.'
                ], 422);
            }
            
            // Validation: Prevent logging if habit is archived
            if ($habit->archived_at && Carbon::parse($validated['date'])->gte(Carbon::parse($habit->archived_at)->startOfDay())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot log for an archived habit.'
                ], 422);
            }

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
     * Get global stats for all habits (Efficiency + 7-day chart)
     */
    public function getStats()
    {
        try {
            $user = User::first();
            $habits = Habit::where('user_id', $user->id)->get();
            
            $totalPossible = 0;
            $totalCompleted = 0;
            
            $today = Carbon::today();
            $sevenDaysAgo = Carbon::today()->subDays(6);
            
            // 1. Calculate Global Efficiency (Last 30 days)
            foreach ($habits as $habit) {
                $createdAt = Carbon::parse($habit->created_at)->startOfDay();
                $archivedAt = $habit->archived_at ? Carbon::parse($habit->archived_at)->startOfDay() : null;
                
                // Effective start: max(created_at, 30 days ago)
                $startDate = $createdAt->gt(Carbon::today()->subDays(30)) ? $createdAt : Carbon::today()->subDays(30);
                
                // Effective end: min(today, archived_at - 1 day)
                $endDate = $archivedAt && $archivedAt->lte(Carbon::today()) ? $archivedAt->subDay() : Carbon::today();
                
                if ($startDate->gt($endDate)) continue; // Never active in this window

                $daysActive = $startDate->diffInDays($endDate) + 1;
                $totalPossible += $daysActive * ($habit->goal ?? 1);
                
                $completedCount = HabitCompletion::where('habit_id', $habit->id)
                    ->where('date', '>=', $startDate->toDateString())
                    ->where('date', '<=', $endDate->toDateString())
                    ->count();
                
                $totalCompleted += $completedCount;
            }
            
            $efficiency = ($totalPossible > 0) ? round(($totalCompleted / $totalPossible) * 100, 2) : 0;
            
            // 2. Build 7-day chart data
            $chartData = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $sevenDaysAgo->copy()->addDays($i);
                $dateString = $date->toDateString();
                
                $dailyPossible = 0;
                foreach ($habits as $habit) {
                    $createdAt = Carbon::parse($habit->created_at)->startOfDay();
                    $archivedAt = $habit->archived_at ? Carbon::parse($habit->archived_at)->startOfDay() : null;
                    
                    // Habit must be created and NOT archived yet on this specific date
                    if ($createdAt->lte($date) && (!$archivedAt || $archivedAt->gt($date))) {
                        $dailyPossible += ($habit->goal ?? 1);
                    }
                }
                
                $dailyCompleted = HabitCompletion::where('user_id', $user->id)
                    ->where('date', $dateString)
                    ->count();
                
                $chartData[] = [
                    'date' => $dateString,
                    'label' => $date->format('D'),
                    'completed' => $dailyCompleted,
                    'possible' => $dailyPossible,
                    'percentage' => ($dailyPossible > 0) ? round(($dailyCompleted / $dailyPossible) * 100, 2) : 0
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'global_efficiency' => min($efficiency, 100),
                    'chart_7_days' => $chartData
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Habit Stats Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate statistics.'
            ], 500);
        }
    }

    /**
     * Get efficiency percentage for a specific habit
     */
    public function getEfficiency($id)
    {
        try {
            $habit = Habit::find($id);
            if (!$habit) {
                return response()->json(['status' => 'error', 'message' => 'Habit not found.'], 404);
            }

            $createdAt = Carbon::parse($habit->created_at)->startOfDay();
            $archivedAt = $habit->archived_at ? Carbon::parse($habit->archived_at)->startOfDay() : null;
            
            $startDate = $createdAt->gt(Carbon::today()->subDays(30)) ? $createdAt : Carbon::today()->subDays(30);
            $endDate = $archivedAt && $archivedAt->lte(Carbon::today()) ? $archivedAt->subDay() : Carbon::today();
            
            if ($startDate->gt($endDate)) {
                 return response()->json(['status' => 'success', 'data' => ['habit_id' => $id, 'efficiency_percentage' => 0]]);
            }

            $daysActive = $startDate->diffInDays($endDate) + 1;
            $totalCompleted = HabitCompletion::where('habit_id', $id)
                ->where('date', '>=', $startDate->toDateString())
                ->where('date', '<=', $endDate->toDateString())
                ->count();

            $expectedSlots = $daysActive * ($habit->goal ?? 1);
            $efficiency = ($expectedSlots > 0) ? round(($totalCompleted / $expectedSlots) * 100, 2) : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'habit_id' => $id,
                    'efficiency_percentage' => min($efficiency, 100)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate efficiency.'
            ], 500);
        }
    }

    /**
     * Archive a habit (Delete from today onwards)
     * Instead of hard deleting, we set archived_at to today.
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

            // Set archived_at to today
            // This means starting from "today", it won't show in the active list.
            $habit->update(['archived_at' => Carbon::now()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Habit archived successfully (Logs are preserved)'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to archive habit.'
            ], 500);
        }
    }
}
