<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        $likes = $this->likes()->with('user')->get();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->user),
            'likes_count' => $likes->count(),
            'likes' => $likes->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user' => new UserResource($like->user),
                ];
            }),
        ];
    }
}