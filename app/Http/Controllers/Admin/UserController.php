<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Resources\admin\UserResource;

class UserController extends Controller
{

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        if ($request->has('sort_by')) {
            $allowedSorts = ['name', 'address', 'email', 'phone'];
            if (in_array($request->sort_by, $allowedSorts)) {
                $query->orderBy($request->sort_by, 'asc');
            }
        }

        $users = $query->paginate(12);

        $totalUsers = User::count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
                                 ->whereYear('created_at', now()->year)
                                 ->count();

        $startOfWeek = now()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek   = now()->endOfWeek(Carbon::SATURDAY);
        $newUsersThisWeek = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        $daysPassed = now()->dayOfWeekIso;
        $averageDailyUsers = $daysPassed > 0 ? ceil($newUsersThisWeek / $daysPassed) : 0;

        return response()->json([
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersThisMonth' => $newUsersThisMonth,
                'newUsersThisWeek' => $newUsersThisWeek,
                'averageDailyUsers' => $averageDailyUsers,
            ],
            'users' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }
}
