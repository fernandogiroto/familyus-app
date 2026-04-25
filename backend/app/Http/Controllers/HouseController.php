<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:10',
        ]);

        $house = House::create([
            'name'       => $data['name'],
            'icon'       => $data['icon'] ?? '🏠',
            'created_by' => $request->user()->id,
        ]);

        $house->users()->attach($request->user()->id);

        return response()->json($this->houseWithDetails($house), 201);
    }

    public function join(Request $request, string $code)
    {
        $house = House::where('invite_code', strtoupper($code))->firstOrFail();

        if ($house->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json($this->houseWithDetails($house));
        }

        if ($house->users()->count() >= 2) {
            return response()->json(['message' => 'Casa já está cheia.'], 422);
        }

        $house->users()->attach($request->user()->id);

        if ($house->users()->count() === 2) {
            $house->update(['status' => 'setup']);
        }

        return response()->json($this->houseWithDetails($house));
    }

    public function show(Request $request, House $house)
    {
        $this->authorize('view', $house);
        return response()->json($this->houseWithDetails($house));
    }

    public function setReady(Request $request, House $house)
    {
        $this->authorize('view', $house);

        $ready = $request->boolean('ready', true);
        $house->users()->updateExistingPivot($request->user()->id, ['is_ready' => $ready]);

        $house->fresh();
        $allReady = $house->users()->wherePivot('is_ready', true)->count() === 2;

        if ($allReady && $house->start_date) {
            $house->update(['status' => 'active', 'game_started_at' => now()]);
        }

        return response()->json($this->houseWithDetails($house->fresh()));
    }

    public function setStartDate(Request $request, House $house)
    {
        $this->authorize('view', $house);

        $data = $request->validate(['start_date' => 'required|date']);
        $house->update(['start_date' => $data['start_date']]);

        return response()->json($this->houseWithDetails($house->fresh()));
    }

    public function checkReady(House $house)
    {
        $house->refresh();
        $allReady = $house->users()->wherePivot('is_ready', true)->count() === 2;

        if ($allReady && $house->start_date && $house->status === 'setup') {
            $house->update(['status' => 'active', 'game_started_at' => now()]);
        }

        return response()->json($this->houseWithDetails($house->fresh()));
    }

    private function houseWithDetails(House $house): array
    {
        $house->load(['users', 'tasks.completions.user', 'prizes', 'weeklyWinners.user']);

        $weekStart = now()->startOfWeek()->toDateString();
        $today     = now()->toDateString();

        $tasks = $house->tasks->where('is_active', true)->map(function ($task) use ($weekStart, $today) {
            $weekCompletions = $task->completions->where('week_start', $weekStart);
            $doingCompletion = $weekCompletions->where('status', 'doing')->first();
            $doneCompletions = $weekCompletions->where('status', 'done')->values();

            // Per-user done count this week
            $doneByUser = $doneCompletions->groupBy('user_id')->map->count();

            // True when ANY user has a done completion for today (shared daily lock)
            $completedToday = $doneCompletions
                ->contains(fn($c) => $c->completion_date?->toDateString() === $today);

            // Which users did this task today (for badge display)
            $doneTodayByUser = $doneCompletions
                ->filter(fn($c) => $c->completion_date?->toDateString() === $today)
                ->pluck('user_id')
                ->unique()
                ->values();

            return array_merge($task->toArray(), [
                'doing_completion'   => $doingCompletion?->load('user'),
                'done_completions'   => $doneCompletions->load('user'),
                'done_by_user'       => $doneByUser,
                'completed_today'    => $completedToday,      // shared daily lock flag
                'done_today_by_user' => $doneTodayByUser,
                'current_completion' => $doingCompletion ?? $doneCompletions->last(),
            ]);
        })->values();

        $users = $house->users->map(function ($user) use ($weekStart, $house) {
            $score = $house->tasks->where('is_active', true)->sum(function ($task) use ($user, $weekStart) {
                $count = $task->completions
                    ->where('user_id', $user->id)
                    ->where('week_start', $weekStart)
                    ->where('status', 'done')
                    ->count();
                return $count * $task->score;
            });

            $totalPossible = $house->tasks->where('is_active', true)->sum(fn($t) => $t->score * $t->weekly_frequency);

            return [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'avatar_url'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'is_ready'       => (bool) $user->pivot->is_ready,
                'weekly_score'   => $score,
                'total_possible' => $totalPossible,
                'percentage'     => $totalPossible > 0 ? round(($score / $totalPossible) * 100) : 0,
            ];
        });

        return [
            'id'              => $house->id,
            'name'            => $house->name,
            'icon'            => $house->icon,
            'invite_code'     => $house->invite_code,
            'status'          => $house->status,
            'start_date'      => $house->start_date?->toDateString(),
            'game_started_at' => $house->game_started_at,
            'created_by'      => $house->created_by,
            'users'           => $users,
            'tasks'           => $tasks,
            'prizes'          => $house->prizes,
            'weekly_winners'  => $house->weeklyWinners->load('user'),
        ];
    }
}
