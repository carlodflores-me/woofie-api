<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Notification;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $user = User::create($validatedData);
        return new UserResource($user);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|unique:users,email,' . $id,
            'password' => 'sometimes|required',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $user = User::findOrFail($id);
        $user->update($validatedData);
        return new UserResource($user);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($request->user()->role == 'admin' || $request->user()->id == $id) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } else {
            return response()->json(['message' => 'Unauthorized action'], 401);
        }
    }

    public function toggleFollow(Request $request, $userId)
    {
        $follower = $request->user();
        $followee = User::find($userId);

        if (!$followee) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($follower->id === $followee->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        if ($follower->isFollowing($followee)) {
            $follower->unfollow($followee);
            return response()->json(['message' => 'Unfollowed successfully']);
        } else {
            $follower->follow($followee);

            // create a notification when a user follows another user
            $data = [
                'type' => 'follow',
                'message' => 'You have a new follower!',
                'follower_id' => $follower->id,
                'follower_name' => $follower->name,
            ];

            Notification::create([
                'user_id' => $followee->id,
                'notifiable_id' => $follower->id,
                'notifiable_type' => 'follow',
                'data' => $data,
            ]);

            return response()->json(['message' => 'Followed successfully']);
        }
    }


    public function verify(Request $request, $id)
    {
        $admin = $request->user();
        $user = User::findOrFail($id);

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized action'], 401);
        }

        $user->is_verified = true;
        $user->save();

        Notification::create([
            'user_id' => $user->id,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'data' => [
                'message' => 'Your account has been verified by ' . $admin->name,
                'type' => 'verification'
            ]
        ]);

        return response()->json(['message' => 'User verified successfully']);
    }

}