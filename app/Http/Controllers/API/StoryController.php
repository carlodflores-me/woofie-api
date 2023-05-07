<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use App\Models\Notification;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function index()
    {
        $stories = Story::where('expires_at', '>', now())->latest()->get();

        return StoryResource::collection($stories);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required|in:image,video',
            'media' => 'required|file|mimetypes:image/jpeg,image/png,video/mp4|max:10240', // Max file size of 10MB
        ]);

        $mediaPath = $request->file('media')->store('public/stories');

        $story = new Story();
        $story->user_id = auth()->id();
        $story->type = $validatedData['type'];
        $story->media_url = Storage::url($mediaPath);
        $story->expires_at = now()->addDay();

        $story->save();

        return new StoryResource($story);
    }


    public function show(Story $story)
    {
        return new StoryResource($story);
    }

    public function destroy(Story $story)
    {
        // Check if the authenticated user is the owner of the story
        if ($story->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $story->delete();

        return response()->noContent();
    }

    public function toggleLike(Request $request, Story $story)
    {
        $user = $request->user();
        $like = $story->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            $like = new Like([
                'user_id' => $user->id,
                'likeable_id' => $story->id,
                'likeable_type' => Story::class,
            ]);
            $like->save();
            $liked = true;

            // Create a notification for the user whose story was liked
            Notification::create([
                'user_id' => $story->user->id,
                'notifiable_id' => $story->id,
                'notifiable_type' => Story::class,
                'data' => [
                    'message' => $user->name . ' liked your story',
                    'type' => 'like'
                ]
            ]);
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $story->likes()->count(),
        ]);
    }

}