<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedResource\Pages;
use App\Filament\Resources\BreedResource\RelationManagers;
use App\Models\Breed;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BreedResource extends Resource
{
    protected static ?string $model = Breed::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        // Cache the API response
        $breeds = Cache::remember('breeds', now()->addDay(), function () {
            return Http::get('https://dog.ceo/api/breeds/list')->json()['message'] ?? [];
        });

        $transformedData = collect($breeds)->map(fn($item) => [
            'value' => $item,
            'label' => ucfirst($item)
        ])->pluck('label', 'value');


        return $table
            ->defaultPaginationPageOption(6)
            ->paginated(false)
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->alignCenter(),
                    Tables\Columns\ImageColumn::make('image')
                        ->alignCenter()
                        ->size(280),

                    Tables\Columns\TextColumn::make('id')
                        ->alignCenter()
                        ->color('primary')
                        ->formatStateUsing(fn(Breed $dogBreed) => $dogBreed->likedUsers($dogBreed))


                ]),


            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('breed')
                    ->label('Breed')
                    ->options($transformedData)
                    ->query(function ($query, $data) {

                        // Get the selected breed from the filter
                        $selectedBreed = $data['value'] ?? 'labrador';

                        // Fetch the rows for the selected breed using Sushi
                        $rows = Breed::getRows($selectedBreed);

                        // Filter the rows manually if needed (just for filtering purposes)
                        $filteredRows = collect($rows); // Convert array to collection

                        // Apply the filter condition
                        if ($selectedBreed) {
                            $filteredRows = $filteredRows->where('name', ucfirst($selectedBreed));
                        }

                        // Return the filtered rows directly as a collection (not a query builder)
                        return $filteredRows; // Return the filtered collection
                    }),

            ])
            ->actions([
                Tables\Actions\Action::make('Like')
                    ->action(fn(Breed $dogBreed) => $dogBreed->likeDog($dogBreed))
                    ->hidden(fn(Breed $dogBreed) => $dogBreed->preferenceExists($dogBreed->name, $dogBreed->image))
                    ->icon('heroicon-o-heart'),

                Tables\Actions\Action::make('Liked')
                    ->action(fn(Breed $dogBreed) => $dogBreed->unlikeDog($dogBreed))
                    ->color('danger')
                    ->hidden(fn(Breed $dogBreed) => $dogBreed->preferenceNotExists($dogBreed->name, $dogBreed->image))
                    ->icon('heroicon-s-heart'),


            ], Tables\Enums\ActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBreeds::route('/'),
        ];
    }


}