<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\{UserResource, PetResource, PostResource, MarketplaceResource};
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Search for users
        $users = DB::table('users')
            ->where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->get();

        // Search for pets
        $pets = DB::table('pets')
            ->where('name', 'like', "%$query%")
            ->orWhere('breed', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->get();

        // Search for posts
        $posts = DB::table('posts')
            ->where('title', 'like', "%$query%")
            ->orWhere('content', 'like', "%$query%")
            ->orWhere('caption', 'like', "%$query%")
            ->get();

        // Search for hashtags in post captions
        $hashtags = DB::table('posts')
            ->select(DB::raw('COUNT(*) as count'))
            ->where('caption', 'like', '%#' . $query . '%')
            ->groupBy('hashtag')
            ->orderByDesc('count')
            ->get();

        // Search for marketplace listings
        $marketplace = DB::table('marketplace')
            ->where('title', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->get();

        // Return the results as a JSON response
        return response()->json([
            'users' => UserResource::collection($users),
            'pets' => PetResource::collection($pets),
            'posts' => PostResource::collection($posts),
            'hashtags' => $hashtags,
            'marketplace' => MarketplaceResource::collection($marketplace)
        ]);
    }

}