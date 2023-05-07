<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'pet_id' => $this->pet_id,
            'pet' => new PetResource($this->whenLoaded('pet')),
            'user' => new UserResource($this->whenLoaded('user')),
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'availability' => $this->availability,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}