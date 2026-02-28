<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

/**
 * Admin UI controller (Blade only)
 * Data operations must be done via REST API (routes/api.php).
 */
class RoomController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.rooms.index');
    }

    public function create()
    {
        return view('admin.rooms.create');
    }

    public function edit(Room $room)
    {
        // Pass ID for client-side fetch
        return view('admin.rooms.edit', ['roomId' => $room->id]);
    }

    /**
     * Legacy web endpoints are disabled. Use API instead.
     */
    public function store(): void { abort(404); }
    public function update(): void { abort(404); }
    public function destroy(): void { abort(404); }
    public function tenant(): void { abort(404); }
}
