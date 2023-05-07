<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'user' => new UserResource($this->user),
            'pets' => PetResource::collection($this->pets),
            'media' => PostMediaResource::collection($this->media),
            'comments' => CommentResource::collection($this->comments),
            'likes_count' => $this->likes_count,
            'liked_by_user' => $this->liked_by_user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}