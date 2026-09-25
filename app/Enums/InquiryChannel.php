<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum InquiryChannel: string
{
    use HasEnumOptions;

    case Chatwoot = 'chatwoot';
    case Whatsapp = 'whatsapp';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Chatwoot => 'Live chat',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
        };
    }
}
