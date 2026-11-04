<?php

namespace App\Enums;

enum ProfessionalStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin solicitud',
            self::Pending => 'Pendiente de aprobación',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
        };
    }
}
