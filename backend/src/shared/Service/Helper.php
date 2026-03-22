<?php

namespace App\shared\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class Helper
{
    public static function slug(string $text, string $separator = '_'): string
    {
        return (new AsciiSlugger())->slug($text, $separator, 'es')->toString();
    }

    public static function slugUpper(string $text, string $separator = '_'): string
    {
        return (new AsciiSlugger())->slug($text, $separator, 'es')->upper()->toString();
    }

    public static function slugLower(string $text, string $separator = '_'): string
    {
        return (new AsciiSlugger())->slug($text, $separator, 'es')->lower()->toString();
    }
}
