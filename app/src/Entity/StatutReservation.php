<?php

namespace App\Entity;

enum StatutReservation: string
{
    case CONFIRMEE = 'confirmee';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMEE => 'Confirmée',
            self::ANNULEE => 'Annulée',
        };
    }
}
