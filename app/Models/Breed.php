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

    protected $fillable = [
        'breedname',
        'imageUrl',
        'liked',
        'user_id',
        'image'
    ];

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


    public static function getRows(): array
    {

        $breed = request('tableFilters')['name']['value'] ?? 'labrador'; // Get breed from filter or default to 'labrador'

        // Fetch data from the API
        $apiUrl = "https://dog.ceo/api/breed/{$breed}/images/random/3";
        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json()['message'] ?? [];
        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($imageUrl) => [
            'image' => $imageUrl,
            'name' => ucfirst($breed),
        ], $data);

    }


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


    public function likedUsers($dogBreed)
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
