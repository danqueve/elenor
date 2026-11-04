<?php

namespace App\Models;

use App\Enums\IvaCondition;
use App\Support\Cuit;
use Database\Factories\FiscalDataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('fiscal_data')]
#[Fillable(['business_name', 'cuit', 'iva_condition'])]
class FiscalData extends Model
{
    /** @use HasFactory<FiscalDataFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'iva_condition' => IvaCondition::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The CUIT as shown to people: "20-12345678-6".
     *
     * @return Attribute<string, never>
     */
    protected function formattedCuit(): Attribute
    {
        return Attribute::get(fn () => Cuit::format($this->cuit));
    }
}
