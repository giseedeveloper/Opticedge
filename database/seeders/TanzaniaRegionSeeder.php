<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class TanzaniaRegionSeeder extends Seeder
{
    /**
     * Seed the 31 official regions of the United Republic of Tanzania.
     *
     * @see https://en.wikipedia.org/wiki/Regions_of_Tanzania
     */
    public function run(): void
    {
        $names = [
            'Arusha',
            'Dar es Salaam',
            'Dodoma',
            'Geita',
            'Iringa',
            'Kagera',
            'Kaskazini Pemba',
            'Kaskazini Unguja',
            'Katavi',
            'Kigoma',
            'Kilimanjaro',
            'Kusini Pemba',
            'Kusini Unguja',
            'Lindi',
            'Manyara',
            'Mara',
            'Mbeya',
            'Mjini Magharibi',
            'Morogoro',
            'Mtwara',
            'Mwanza',
            'Njombe',
            'Pwani',
            'Rukwa',
            'Ruvuma',
            'Shinyanga',
            'Simiyu',
            'Singida',
            'Songwe',
            'Tabora',
            'Tanga',
        ];

        foreach ($names as $name) {
            Region::firstOrCreate(
                ['name' => $name],
            );
        }
    }
}
