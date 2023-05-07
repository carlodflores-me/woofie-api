<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Models\PetAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PetController extends Controller
{

    public function index(Request $request)
    {
        $userId = $request->user_id;
        $pets = Pet::where('user_id', $userId)->with('user')->get();
        return PetResource::collection($pets);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'breed' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date_format:Y-m-d',
            'gender' => 'nullable|in:male,female',
            'description' => 'nullable|string',
            'attachment' => 'nullable|image|max:10240', // Up to 10MB,
            'profile_picture' => 'nullable|image|max:10240', // Up to 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        // Create the new pet instance
        $pet = new Pet();
        $pet->name = $request->name;
        $pet->species = $request->species;
        $pet->breed = $request->breed;
        $pet->birthdate = $request->birthdate;
        $pet->gender = $request->gender;
        $pet->description = $request->description;
        $pet->user_id = $request->user()->id;

        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('public/pet_profile_pictures');
            $pet->profile_picture = $profilePicturePath;
        }

        // Store the attachment if provided
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $attachmentPath = $attachment->store('public/pet_attachments');
            $attachmentUrl = Storage::url($attachmentPath);

            $petAttachment = new PetAttachment();
            $petAttachment->url = $attachmentUrl;
            $petAttachment->type = $attachment->getClientMimeType();
            $petAttachment->pet_id = $pet->id;
            $petAttachment->save();
        }

        $pet->save();

        return new PetResource($pet);
    }

    public function getByUserId(Request $request, $userId)
    {
        $pets = Pet::where('user_id', $userId)->get();
        return PetResource::collection($pets);
    }


    public function show($id)
    {
        $pet = Pet::with('user')->find($id);
        if (!$pet) {
            return response()->json(['error' => 'Pet not found'], 404);
        }
        return new PetResource($pet);
    }


    public function update(Request $request, Pet $pet)
    {
        $user = $request->user();

        if (!$user->ownsPet($pet)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'breed' => 'string|max:255',
            'age' => 'integer|min:0',
            'species' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $pet->name = $request->input('name', $pet->name);
        $pet->breed = $request->input('breed', $pet->breed);
        $pet->age = $request->input('age', $pet->age);
        $pet->species = $request->input('species', $pet->species);

        $pet->save();

        return new PetResource($pet);
    }

    public function destroy($id)
    {
        $pet = Pet::find($id);

        if (!$pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ($pet->user_id != auth()->user()->id) {
            return response()->json(['message' => 'You are not authorized to delete this pet'], 403);
        }

        $pet->delete();

        return response()->json(['message' => 'Pet deleted successfully']);
    }
}