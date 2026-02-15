<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Religion;
use App\Models\Caste;
use App\Models\SubCaste;

class ReligionCasteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks to allow truncation
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('sub_castes')->truncate();
        \DB::table('castes')->truncate();
        \DB::table('religions')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            'Hindu' => [
                'Brahmin' => ['Iyer', 'Iyengar', 'Namboodiri'],
                'Nair' => ['Menon', 'Pillai', 'Panicker'],
                'Ezhava' => ['Thiyya', 'Billava'],
                'Viswakarma' => ['Achari', 'Kammalar'],
                'SC/ST' => ['Pulaya', 'Paraya', 'Kurava'],
            ],
            'Muslim' => [
                'Sunni' => ['Shafi', 'Hanafi'],
                'Shia' => ['Ithna Ashari', 'Ismaili'],
                'Mujahid' => [],
                'Mapila' => [],
            ],
            'Christian' => [
                'Catholic' => ['Roman Catholic', 'Syro Malabar', 'Syro Malankara'],
                'Jacobite' => [],
                'Orthodox' => [],
                'Marthoma' => [],
                'Pentecostal' => [],
                'CSI' => [],
            ],
            'Sikh' => [
                'Jat' => [],
                'Ramgharia' => [],
                'Arora' => [],
            ],
            'Jain' => [
                'Digambar' => [],
                'Shvetambar' => [],
            ],
            'Buddhist' => [],
            'Parsi' => [],
            'Jewish' => [],
            'Other' => [],
        ];

        $religionOrder = 1;

        foreach ($data as $religionName => $castes) {
            $religion = Religion::create([
                'name' => $religionName,
                'is_active' => true,
                'order_number' => $religionOrder++,
            ]);

            if (is_array($castes)) {
                $casteOrder = 1;
                foreach ($castes as $casteName => $subCastes) {
                    // Check if the caste name is the key or value (for empty subcastes)
                    $actualCasteName = is_string($casteName) ? $casteName : $subCastes;
                    $actualSubCastes = is_string($casteName) ? $subCastes : [];

                    $caste = Caste::create([
                        'religion_id' => $religion->id,
                        'name' => $actualCasteName,
                        'is_active' => true,
                        'order_number' => $casteOrder++,
                    ]);

                    if (!empty($actualSubCastes)) {
                        $subCasteOrder = 1;
                        foreach ($actualSubCastes as $subCasteName) {
                            SubCaste::create([
                                'caste_id' => $caste->id,
                                'name' => $subCasteName,
                                'is_active' => true,
                                'order_number' => $subCasteOrder++,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
