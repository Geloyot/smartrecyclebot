<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use App\Models\BinReading;
use App\Models\SystemThreshold;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BinController extends Controller
{
    public function binReadingRead(Request $request) {
        $validated = $request->validate([
            'bio' => 'required|numeric|min:0|max:100',
            'nonbio' => 'required|numeric|min:0|max:100',
        ]);

        DB::table('bin_readings')->insert([
            ['bin_id' => 1, 'fill_level' => $validated['bio'], 'created_at' => now(), 'updated_at' => now()],
            ['bin_id' => 2, 'fill_level' => $validated['nonbio'], 'created_at' => now(), 'updated_at' => now()],
        ]);

        return response()->json(['status' => 'saved']);
    }

    public function binStatus()
    {
        $bins = Bin::with(['readings' => fn($q) => $q->latest()->limit(1)])->get();
        $threshold = SystemThreshold::getValue('full_bin_threshold', 80);

        $status = [];
        foreach ($bins as $bin) {
            $fill = $bin->readings->first()?->fill_level ?? $bin->last_fill_level;
            $status[$bin->type] = [
                'name'       => $bin->name,
                'fill_level' => $fill,
                'is_full'    => $fill !== null && $fill >= $threshold,
                'threshold'  => $threshold,
            ];
        }

        return response()->json($status);
    }
}
