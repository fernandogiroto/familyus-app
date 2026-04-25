<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function store(Request $request, House $house)
    {
        $this->authorize('view', $house);

        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'score'            => 'required|integer|min:1',
            'weekly_frequency' => 'required|integer|min:1|max:7',
            'category'         => 'nullable|string|max:80',
        ]);

        $task = $house->tasks()->create(array_merge($data, ['created_by' => $request->user()->id]));

        return response()->json($task->load('creator'), 201);
    }

    public function update(Request $request, House $house, Task $task)
    {
        $this->authorize('view', $house);

        $data = $request->validate([
            'name'             => 'sometimes|string|max:150',
            'score'            => 'sometimes|integer|min:1',
            'weekly_frequency' => 'sometimes|integer|min:1|max:7',
            'category'         => 'nullable|string|max:80',
        ]);

        $task->update($data);

        return response()->json($task->fresh()->load('creator'));
    }

    public function destroy(House $house, Task $task)
    {
        $this->authorize('view', $house);
        $task->update(['is_active' => false]);
        return response()->json(['message' => 'Tarefa removida.']);
    }

    public function startDoing(Request $request, House $house, Task $task)
    {
        $this->authorize('view', $house);

        $weekStart = now()->startOfWeek()->toDateString();
        $userId = $request->user()->id;

        $today = now()->toDateString();

        // Block if another user is currently doing it
        $activeDoing = TaskCompletion::where('task_id', $task->id)
            ->where('week_start', $weekStart)
            ->where('status', 'doing')
            ->first();

        if ($activeDoing && $activeDoing->user_id !== $userId) {
            return response()->json(['message' => 'Tarefa já está sendo feita por outro membro.'], 422);
        }

        if ($activeDoing && $activeDoing->user_id === $userId) {
            return response()->json(['message' => 'Você já está fazendo esta tarefa.'], 422);
        }

        // Block if ANYONE already completed this task today (shared daily lock)
        $completedToday = TaskCompletion::where('task_id', $task->id)
            ->where('completion_date', $today)
            ->where('status', 'done')
            ->exists();

        if ($completedToday) {
            return response()->json(['message' => 'Esta tarefa já foi realizada hoje. Volta amanhã!'], 422);
        }

        // Also block if the current user already has a doing record for today
        $doingToday = TaskCompletion::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->where('completion_date', $today)
            ->where('status', 'doing')
            ->exists();

        if ($doingToday) {
            return response()->json(['message' => 'Você já está fazendo esta tarefa.'], 422);
        }

        // Check weekly frequency cap
        $doneThisWeek = TaskCompletion::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->where('week_start', $weekStart)
            ->where('status', 'done')
            ->count();

        if ($doneThisWeek >= $task->weekly_frequency) {
            return response()->json(['message' => 'Você já completou esta tarefa o máximo de vezes esta semana.'], 422);
        }

        $completion = TaskCompletion::create([
            'task_id'         => $task->id,
            'user_id'         => $userId,
            'status'          => 'doing',
            'started_at'      => now(),
            'week_start'      => $weekStart,
            'completion_date' => $today,
        ]);

        return response()->json($completion->load('user'));
    }

    public function cancelDoing(Request $request, House $house, Task $task)
    {
        $this->authorize('view', $house);

        $weekStart = now()->startOfWeek()->toDateString();

        $completion = TaskCompletion::where('task_id', $task->id)
            ->where('user_id', $request->user()->id)
            ->where('week_start', $weekStart)
            ->where('status', 'doing')
            ->firstOrFail();

        $completion->delete();

        return response()->json(['message' => 'Tarefa cancelada.']);
    }

    public function completeDoing(Request $request, House $house, Task $task)
    {
        $this->authorize('view', $house);

        $weekStart = now()->startOfWeek()->toDateString();

        $completion = TaskCompletion::where('task_id', $task->id)
            ->where('user_id', $request->user()->id)
            ->where('week_start', $weekStart)
            ->where('status', 'doing')
            ->firstOrFail();

        $completion->update([
            'status'       => 'done',
            'completed_at' => now(),
        ]);

        return response()->json($completion->load('user'));
    }

    public function uploadPhoto(Request $request, House $house, Task $task)
    {
        $this->authorize('view', $house);

        $request->validate(['image' => 'required|image|max:5120']);

        $weekStart = now()->startOfWeek()->toDateString();

        // Get the most recent done completion without photo
        $completion = TaskCompletion::where('task_id', $task->id)
            ->where('user_id', $request->user()->id)
            ->where('week_start', $weekStart)
            ->where('status', 'done')
            ->whereNull('image_path')
            ->latest()
            ->firstOrFail();

        $path = $request->file('image')->store('task-photos', 'public');
        $completion->update(['image_path' => $path]);

        return response()->json($completion->load('user'));
    }
}
