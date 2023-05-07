<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
{
    public function toArray($request)
    {
        $totalLikes = $this->posts->sum(function ($post) {
            return $post->likes()->count();
        });

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'breed' => $this->breed,
            'total_likes' => $totalLikes,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}