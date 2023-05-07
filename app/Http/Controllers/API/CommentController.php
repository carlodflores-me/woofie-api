<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Like;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $comments = Comment::with('user')->paginate(10);
        return CommentResource::collection($comments);
    }

    public function show(Request $request, Comment $comment)
    {
        $comment->load('user');
        return new CommentResource($comment);
    }

    public function store(Request $request, Post $post)
    {
        $validatedData = $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment = new Comment();
        $comment->user_id = $request->user()->id;
        $comment->post_id = $post->id;
        $comment->content = $validatedData['content'];
        $comment->save();

        if ($post->user_id !== $request->user()->id) {
            Notification::create([
                'user_id' => $post->user_id,
                'message' => 'Someone commented on your post.',
                'notifiable_id' => $post->id,
                'notifiable_type' => Post::class,
                'type' => 'comment',
            ]);
        }

        $comment->load('user');
        return new CommentResource($comment);
    }

    public function update(Request $request, Comment $comment)
    {

        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'You are not authorized to update this comment'], 403);
        }

        $validatedData = $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment->content = $validatedData['content'];
        $comment->save();

        $comment->load('user');
        return new CommentResource($comment);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'You are not authorized to delete this comment'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted.']);
    }

    public function toggleLike(Request $request, Comment $comment)
    {
        $user = $request->user();
        $like = $comment->likes()->where('user_id', $user->id)->first();
        $liked = false;

        if ($like) {
            $like->delete();
        } else {
            $like = new Like([
                'user_id' => $user->id,
                'likeable_id' => $comment->id,
                'likeable_type' => Comment::class,
            ]);
            $like->save();

            // Create a notification for the comment author
            Notification::create([
                'user_id' => $comment->user_id,
                'notifiable_id' => $comment->id,
                'notifiable_type' => Comment::class,
                'data' => [
                    'type' => 'like',
                    'message' => $user->name . ' liked your comment',
                ],
            ]);

            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $comment->likes()->count(),
        ]);
    }


}