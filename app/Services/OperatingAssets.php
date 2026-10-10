<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OperatingAssets
{
    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow) {}

    public function nextTag(): string
    {
        $last = DB::table('operating_records')->where('type', 'assets')->where('data->asset_tag', 'like', 'AST-%')->get()
            ->map(fn (object $asset): int => (int) substr((string) ($this->catalog->data($asset)['asset_tag'] ?? ''), 4))->max() ?? 0;

        return 'AST-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Case- and space-insensitive lookup among records still in use, so a tag or serial number identifies one item.
     */
    public function isTaken(string $type, string $key, string $value, ?int $exceptId = null): bool
    {
        $needle = mb_strtolower(trim($value));

        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->get()
            ->contains(fn (object $record): bool => mb_strtolower(trim((string) ($this->catalog->data($record)[$key] ?? ''))) === $needle);
    }
}
