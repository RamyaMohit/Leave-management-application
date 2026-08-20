<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::query();

        if ($request->filled('year')) {
            $query->whereYear('holiday_date', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('holiday_date', $request->month);
        }

        $holidays = $query->orderBy('holiday_date', 'asc')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($holidays);
        }

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'holiday_name' => ['required', 'string', 'max:255'],
        ]);

        $holiday = Holiday::create($validated);

        AuditLogService::log(
            action: 'CREATE_HOLIDAY',
            entityType: 'holiday',
            entityId: $holiday->id,
            newValue: $holiday->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Holiday added successfully',
                'holiday' => $holiday
            ], 201);
        }

        return redirect()->route('holidays.index')->with('success', 'Holiday added successfully.');
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'holiday_date' => ['required', 'date', Rule::unique('holidays')->ignore($holiday->id)],
            'holiday_name' => ['required', 'string', 'max:255'],
        ]);

        $oldValue = $holiday->toArray();
        $holiday->update($validated);

        AuditLogService::log(
            action: 'UPDATE_HOLIDAY',
            entityType: 'holiday',
            entityId: $holiday->id,
            oldValue: $oldValue,
            newValue: $holiday->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Holiday updated successfully',
                'holiday' => $holiday
            ]);
        }

        return redirect()->route('holidays.index')->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);
        $oldValue = $holiday->toArray();

        $holiday->delete();

        AuditLogService::log(
            action: 'DELETE_HOLIDAY',
            entityType: 'holiday',
            entityId: $id,
            oldValue: $oldValue
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Holiday deleted successfully']);
        }

        return redirect()->route('holidays.index')->with('success', 'Holiday deleted successfully.');
    }
}
