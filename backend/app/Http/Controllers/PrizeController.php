<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Prize;
use App\Models\WeeklyWinner;
use Illuminate\Http\Request;

class PrizeController extends Controller
{
    public function store(Request $request, House $house)
    {
        $this->authorize('view', $house);

        $data = $request->validate(['name' => 'required|string|max:150']);
        $prize = $house->prizes()->create($data);

        return response()->json($prize, 201);
    }

    public function destroy(House $house, Prize $prize)
    {
        $this->authorize('view', $house);
        $prize->delete();
        return response()->json(['message' => 'Prêmio removido.']);
    }

    public function selectWinnerPrize(Request $request, House $house)
    {
        $this->authorize('view', $house);

        $data = $request->validate([
            'prize_id'   => 'required|exists:prizes,id',
            'week_start' => 'required|date',
        ]);

        $winner = WeeklyWinner::where('house_id', $house->id)
            ->where('week_start', $data['week_start'])
            ->firstOrFail();

        if ($winner->is_tie) {
            return response()->json(['message' => 'Não é possível escolher prêmio em caso de empate.'], 422);
        }

        if ($winner->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Apenas o vencedor pode escolher o prêmio.'], 403);
        }

        $prize = Prize::findOrFail($data['prize_id']);

        $winner->update([
            'prize_id'          => $prize->id,
            'prize_name'        => $prize->name,
            'prize_selected_at' => now(),
        ]);

        return response()->json($winner->load('user', 'prize'));
    }

    public function checkWeeklyReset(House $house)
    {
        $this->authorize('view', $house);

        // Only process the PREVIOUS week — the current week is still in progress
        $prevWeekStart = now()->subWeek()->startOfWeek()->toDateString();
        $prevWeekEnd   = now()->subWeek()->endOfWeek()->toDateString();

        // Check if we already have a winner for the previous week
        $existingWinner = WeeklyWinner::where('house_id', $house->id)
            ->where('week_start', $prevWeekStart)
            ->first();

        if ($existingWinner) {
            // Only surface the banner when the prize hasn't been claimed yet
            if (!$existingWinner->prize_name) {
                return response()->json(['winner' => $existingWinner->load('user', 'prize'), 'week_start' => $prevWeekStart]);
            }
            return response()->json(['winner' => null]);
        }

        // Calculate scores for the previous week
        $users = $house->users()->withPivot('weekly_score')->get();

        $scores = $users->map(function ($user) use ($house, $prevWeekStart) {
            $score = $house->tasks()->where('is_active', true)->get()->sum(function ($task) use ($user, $prevWeekStart) {
                return $task->completions()
                    ->where('user_id', $user->id)
                    ->where('week_start', $prevWeekStart)
                    ->where('status', 'done')
                    ->count() * $task->score;
            });
            return ['user' => $user, 'score' => $score];
        })->sortByDesc('score')->values();

        $topScore  = $scores->get(0);
        $runnerUp  = $scores->get(1);

        if (!$topScore || $topScore['score'] === 0) {
            return response()->json(['winner' => null]);
        }

        // Detect tie
        $isTie = $runnerUp && $runnerUp['score'] === $topScore['score'];

        $winner = WeeklyWinner::create([
            'house_id'   => $house->id,
            'user_id'    => $isTie ? null : $topScore['user']->id,
            'week_start' => $prevWeekStart,
            'is_tie'     => $isTie,
        ]);

        // Reset scores for the new week
        foreach ($users as $user) {
            $house->users()->updateExistingPivot($user->id, ['weekly_score' => 0]);
        }

        return response()->json(['winner' => $winner->load('user', 'prize'), 'week_start' => $prevWeekStart]);
    }
}
