<?php

namespace App\Support;

use App\Models\ProductListItem;
use Illuminate\Database\Eloquent\Builder;

class AssignableImeiMatcher
{
    /**
     * @param  Builder<ProductListItem>  $query
     */
    public static function findMatch(Builder $query, string $raw): ?ProductListItem
    {
        $normalized = preg_replace('/\s+/u', '', trim($raw)) ?? trim($raw);

        $items = (clone $query)->orderBy('imei_number')->get(['id', 'imei_number', 'model']);

        foreach ($items as $item) {
            $im = trim((string) $item->imei_number);
            if ($im === '') {
                continue;
            }
            $imNorm = preg_replace('/\s+/u', '', $im) ?? $im;
            if (strcasecmp($im, trim($raw)) === 0 || strcasecmp($imNorm, $normalized) === 0) {
                return $item;
            }
        }

        foreach ($items as $item) {
            $im = trim((string) $item->imei_number);
            if ($im === '') {
                continue;
            }
            if (stripos($raw, $im) !== false || stripos($normalized, preg_replace('/\s+/u', '', $im) ?? $im) !== false) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{id: int, imei_number: string|null, model: string|null, text: string}>
     */
    public static function mapRows(\Illuminate\Support\Collection $items): array
    {
        return $items->map(fn ($i) => [
            'id' => $i->id,
            'imei_number' => $i->imei_number,
            'model' => $i->model,
            'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
        ])->values()->all();
    }
}
