<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($auditLogs);
        }

        return view('audit_logs.index', compact('auditLogs'));
    }
}
