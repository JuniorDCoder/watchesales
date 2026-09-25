<?php

namespace App\Models;

use Database\Factories\SearchQueryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A search made by a visitor in the catalogue, used to spot demand.
 *
 * @property int $id
 * @property string $term
 * @property int $results_count
 * @property string $visitor_hash
 * @property Carbon|null $created_at
 */
#[Fillable(['term', 'results_count', 'visitor_hash'])]
class SearchQuery extends Model
{
    /** @use HasFactory<SearchQueryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;
}
