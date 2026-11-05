<?php

namespace App\Enums;

/**
 * The orders a catalog listing can have. The values are what appears in the
 * URL (?orden=menor-precio).
 */
enum CatalogSort: string
{
    case Relevance = 'relevancia';
    case Name = 'nombre';
    case PriceAsc = 'menor-precio';
    case PriceDesc = 'mayor-precio';
    case Newest = 'novedades';

    public function label(): string
    {
        return match ($this) {
            self::Relevance => 'Más relevantes',
            self::Name => 'Nombre (A-Z)',
            self::PriceAsc => 'Menor precio',
            self::PriceDesc => 'Mayor precio',
            self::Newest => 'Novedades',
        };
    }
}
