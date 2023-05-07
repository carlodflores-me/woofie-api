<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceResource;
use App\Models\Marketplace;
use App\Models\Like;
use Illuminate\Http\Request;
use Validator;

class MarketplaceController extends Controller
{
    public function index()
    {
        $marketplaces = Marketplace::with('user')->get();

        return MarketplaceResource::collection($marketplaces);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'price' => 'required|numeric',
            'availability' => 'required|in:available,not_available',
            'type' => 'required|in:adoption,sale,stud_service',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $marketplace = new Marketplace();
        $marketplace->name = $request->input('name');
        $marketplace->description = $request->input('description');
        $marketplace->price = $request->input('price');
        $marketplace->availability = $request->input('availability');
        $marketplace->type = $request->input('type');
        $marketplace->user_id = $user->id;
        $marketplace->save();

        return new MarketplaceResource($marketplace->load('user'));
    }

    public function show(Marketplace $marketplace)
    {
        $marketplace->load('user');

        return new MarketplaceResource($marketplace);
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:500',
            'price' => 'sometimes|required|numeric',
            'availability' => 'sometimes|required|in:available,not_available',
            'type' => 'sometimes|required|in:adoption,sale,stud_service',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check if the authenticated user owns the marketplace listing being updated
        if ($marketplace->user_id !== $user->id) {
            return response()->json([
                'error' => 'You do not have permission to update this marketplace listing',
            ], 403);
        }

        $marketplace->name = $request->input('name', $marketplace->name);
        $marketplace->description = $request->input('description', $marketplace->description);
        $marketplace->price = $request->input('price', $marketplace->price);
        $marketplace->availability = $request->input('availability', $marketplace->availability);
        $marketplace->type = $request->input('type', $marketplace->type);
        $marketplace->save();

        return new MarketplaceResource($marketplace->load('user'));
    }

    public function destroy(Marketplace $marketplace)
    {
        // Check if the authenticated user is the owner of the marketplace
        if ($marketplace->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $marketplace->delete();

        return response()->noContent();
    }

    public function toggleLike(Request $request, Marketplace $marketplace)
    {
        $user = $request->user();
        $like = $marketplace->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            $like = new Like([
                'user_id' => $user->id,
                'likeable_id' => $marketplace->id,
                'likeable_type' => Marketplace::class,
            ]);
            $like->save();
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $marketplace->likes()->count(),
        ]);
    }

}