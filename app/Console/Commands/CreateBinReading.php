<?php

namespace App\Console\Commands;

use App\Models\Bin;
use App\Models\BinReading;
use Illuminate\Console\Command;

class CreateBinReading extends Command
{
    protected $signature = 'bin:reading {bio} {nonbio}';
    protected $description = 'Create a bin reading. 1st arg = Bio fill level, 2nd arg = Non-Bio fill level OR B/N label with single value.';

    public function handle()
    {
        $first  = $this->argument('bio');
        $second = strtoupper($this->argument('nonbio'));

        // Mode 1: both are numeric (e.g. 90 45)
        if (is_numeric($first) && is_numeric($second)) {
            $this->createReading('bio', (float) $first);
            $this->createReading('non-bio', (float) $second);
            return 0;
        }

        // Mode 2: single value + label (e.g. 90 B or 90 N)
        if (is_numeric($first) && in_array($second, ['B', 'N'])) {
            $type = $second === 'B' ? 'bio' : 'non-bio';
            $this->createReading($type, (float) $first);
            return 0;
        }

        $this->error('Invalid arguments. Usage: bin:reading 90 45 OR bin:reading 90 B');
        return 1;
    }

    private function createReading(string $type, float $fill): void
    {
        if ($fill < 0 || $fill > 100) {
            $this->error("Fill level {$fill} is out of range (0-100). Skipping {$type}.");
            return;
        }

        $bin = Bin::where('type', $type)->first();

        if (!$bin) {
            $this->error("No bin found with type '{$type}'.");
            return;
        }

        BinReading::create([
            'bin_id'     => $bin->id,
            'fill_level' => $fill,
        ]);

        $this->info("✅ Bin reading created: {$bin->name} at {$fill}%");
    }
}
