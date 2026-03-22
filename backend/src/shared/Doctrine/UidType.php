<?php

namespace App\shared\Doctrine;

use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

final class UidType
{
    // ULID
    //    public const string NAME = UlidType::NAME;
    //    public const string REGEX = '[0-7][0-9A-HJKMNP-TV-Z]{25}';

    // UUID
    public const NAME = UuidType::NAME;
    public const REGEX = '[1-9A-HJ-NP-Za-km-z]{22}'; // For Base58
    public const REGEX_PATERN = '/^[1-9A-HJ-NP-Za-km-z]{22}$/'; // For Base58
    //    public const string REGEX = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'; // For Rfc4122

    public static function generate(): AbstractUid
    {
        return Uuid::v4();
    }

    public static function fromString(string $value): AbstractUid
    {
        return Uuid::fromBase58($value);
    }

    public static function toString(?AbstractUid $value): ?string
    {
        return $value?->toBase58();
    }
}
