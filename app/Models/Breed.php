<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Sushi\Sushi;

class Breed extends Model
{
    use Sushi;

    protected static $rows = [];
    protected $connection = null;
    protected $fillable = [
        'breedname',
        'imageUrl',
        'liked',
        'user_id',
        'image'
    ];

    // Use the Sushi array source

    public static function getFilterOptions()
    {
        // Fetch the list of breeds from the API
        $breeds = Http::get('https://dog.ceo/api/breeds/list')->json();
        $breedName = [];


        // Loop through the breeds and add them to the options array
        foreach ($breeds['message'] as $name => $subBreeds) {
            $breedName[] = $subBreeds;
        }

        return $breedName;
    }

    // Method to fetch images based on a breed
    public static function getRows($breed = 'labrador'): array
    {
        // Fetch images from the API based on the selected breed
        $url = "https://dog.ceo/api/breed/{$breed}/images/random/6";
        $response = Http::get($url);

        // Return an empty array if the request fails
        if (!$response->successful()) {
            return [];
        }

        $breeds = $response->json();
        $img = [];

        // Ensure 'message' exists in the response and process images
        if (isset($breeds['message'])) {
            foreach ($breeds['message'] as $imageUrl) {
                $img[] = [
                    'image' => $imageUrl,
                    'name' => ucfirst($breed), // Capitalized breed name
                ];
            }
        }

        return $img;
    }

    public static function setRows(array $rows): void
    {
        static::$rows = $rows;
    }
//
//    public static function fetchImages($breed = null): array
//    {
//        $url = "https://dog.ceo/api/breed/$breed/images/random/6";
//        $response = Http::get($url);
//
//        if (!$response->successful()) {
//            return [];
//        }
//
//        $breeds = $response->json();
//        $img = [];
//
//        if (isset($breeds['message'])) {
//            foreach ($breeds['message'] as $imageUrl) {
//                $img[] = [
//                    'image' => $imageUrl,
//                    'name' => ucfirst($breed), // Capitalize breed name
//                ];
//            }
//        }
//
//        return $img;
//    }

    public function likeDog($dogBreed)
    {

        // Check if exists
        $existingPreference = auth()->user()->dogPreferences()
            ->where('breed', $dogBreed->name)
            ->where('image', $dogBreed->image)
            ->first();

        if (!$existingPreference) {
            // Only create if the preference doesn't already exist
            auth()->user()->dogPreferences()->create([
                'breed' => $dogBreed->name,
                'image' => $dogBreed->image,
            ]);
        }


        return back()->with('success', 'Dog liked!');
    }

    public function userPref(): HasMany
    {
        return $this->hasMany(UserDogPreference::class, 'user_id');

    }

    public function unlikeDog($dogBreed)
    {

        // Check if exists
        $existingPreference = auth()->user()->dogPreferences()
            ->where('breed', $dogBreed->name)
            ->where('image', $dogBreed->image)
            ->first();

        if ($existingPreference) {
            // Only delete if the preference doesn't already exist
            $unlikeDog = UserDogPreference::find($existingPreference->id);
            $unlikeDog->delete();
        }

    }

    public function preferenceExists($name, $image)
    {
        return auth()->user()->dogPreferences()
            ->where('breed', $name)
            ->where('image', $image)
            ->where('user_id', auth()->user()->id)
            ->exists(); // Returns true if it exists, false otherwise
    }

    public function preferenceNotExists($name, $image)
    {
        return !auth()->user()->dogPreferences()
            ->where('breed', $name)
            ->where('image', $image)
            ->where('user_id', auth()->user()->id)
            ->exists();
    }

    // Default rows loaded by Sushi

    public function likedUSers($dogBreed)
    {
        $users = User::whereHas('dogPreferences', function ($query) use ($dogBreed) {
            $query->where('breed', $dogBreed->name)
                ->where('image', $dogBreed->image);
        })->get();

        $currentUserId = auth()->id();

        // Filter the users, separating the current user and others
        $otherUsers = $users->filter(fn($user) => $user->id !== $currentUserId)->pluck('name');

        // If no users liked the picture, return an empty string
        if ($users->isEmpty()) {
            return '';
        }

        // Prepare the list of users, including "You" if the current user liked it
        $allUsers = collect();
        if ($users->contains('id', $currentUserId)) {
            $allUsers->push('You');
        }

        // Merge other users into the list
        $allUsers = $allUsers->merge($otherUsers);

        // Check if the total user count is greater than 3 and format accordingly
        if ($allUsers->count() > 3) {
            $firstTwo = $allUsers->take(2)->join(', ');
            $remainingCount = $allUsers->count() - 2;
            return "$firstTwo, and $remainingCount others liked this.";
        }

        // If there are 3 or fewer users, join and append "liked this"
        return $allUsers->join(', ', ' and ').' liked this.';

    }

    // Method to fetch images from the API

    /**
     * Get the list of users who have a matching breed and image in their preferences.
     *
     * @param  string  $name
     * @param  string  $image
     * @return Collection
     */
    public function getUsersByPreference($name, $image)
    {
        return User::whereHas('dogPreferences', function ($query) use ($name, $image) {
            $query->where('breed', $name)->where('image', $image);
        })->get();
    }
}
