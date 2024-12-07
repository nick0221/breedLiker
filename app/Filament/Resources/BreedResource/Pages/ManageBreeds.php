<?php

namespace App\Filament\Resources\BreedResource\Pages;

use App\Filament\Resources\BreedResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBreeds extends ManageRecords
{
    protected static string $resource = BreedResource::class;
    protected $listeners = ['refreshTable' => 'refreshTableData'];


    protected function getHeaderActions(): array
    {
        return [

        ];
    }


}
