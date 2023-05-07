<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Like;
use App\Models\PostMedia;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use DB;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::where('published', true)
            ->with('user', 'pets', 'comments', 'likes')
            ->latest()
            ->paginate(10);

        return PostResource::collection($posts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'required|string|max:255',
            'media.*' => 'required|file|mimes:jpeg,png,mp4|max:10240',
            // Max size of 10MB
            'pet_id' => 'nullable|exists:pets,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $post = new Post();
        $post->caption = $request->input('caption');
        $post->user_id = $user->id;

        if ($request->has('pet_id')) {
            $post->pet_id = $request->input('pet_id');
        }

        $post->save();

        // Store the uploaded media files and associate them with the new post
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $media = new PostMedia();
                $media->post_id = $post->id;
                $media->file_path = $file->store('public/posts/media');
                $media->save();
            }
        }

        preg_match_all('/@([\w\-]+)/', $request->input('caption'), $matches);
        $mentionedUsers = $matches[1];

        // Loop through the mentioned users and notify them
        foreach ($mentionedUsers as $mentionedUser) {
            $user = User::where('username', $mentionedUser)->first();
            if ($user && $user->id !== $request->user()->id) {
                Notification::create([
                    'user_id' => $user->id,
                    'notifiable_id' => $post->id,
                    'notifiable_type' => Post::class,
                    'data' => [
                        'type' => 'mention',
                        'message' => 'You were mentioned in a post caption by ' . $request->user()->name,
                    ],
                ]);
            }
        }

        return new PostResource($post->load('user', 'pet', 'media'));
    }

    public function show(Post $post)
    {
        $post->load('user', 'pets', 'comments.user', 'likes.user');

        return new PostResource($post);
    }

    public function update(Request $request, Post $post)
    {
        $post->title = $request->input('title', $post->title);
        $post->body = $request->input('body', $post->body);
        $post->published = $request->input('published', $post->published);
        $post->save();

        return new PostResource($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->noContent();
    }

    public function toggleLike(Request $request, Post $post)
    {
        $user = $request->user();

        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $isLiked = false;
        } else {
            $like = new Like();
            $like->user_id = $user->id;
            $like->likeable_id = $post->id;
            $like->likeable_type = Post::class;
            $like->save();
            $isLiked = true;
        }

        $likeCount = $post->likes()->count();
        return response()->json(['liked' => $isLiked, 'like_count' => $likeCount]);
    }


    public function share(Request $request, Post $post)
    {
        $user = $request->user();

        $sharedPost = new Post([
            'caption' => $post->caption,
            'location' => $post->location,
            'media_url' => $post->media_url,
            'media_type' => $post->media_type,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
        ]);

        $sharedPost->user_id = $user->id;
        $sharedPost->original_post_id = $post->id;
        $post->shares_count += 1;

        DB::transaction(function () use ($post, $sharedPost, $user) {
            $post->save();
            $sharedPost->save();

            // Add notification
            Notification::create([
                'user_id' => $user->id,
                'notifiable_id' => $post->id,
                'notifiable_type' => Post::class,
                'type' => 'share',
                'data' => [
                    'message' => $user->name . ' shared your post',
                    'type' => 'share',
                ],
            ]);
        });

        return new PostResource($sharedPost);
    }


}