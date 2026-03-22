<?php

namespace App\shared\Service;

class TextCleaner
{
    public function username(?string $username): ?string
    {
        if (null === $username) {
            return null;
        }

        $username = mb_strtolower($username, 'UTF-8');
        $username = $this->removeAccents($username);
        $username = preg_replace('/[^a-z0-9_.]/', '', $username);
        $username = preg_replace('/[_.]{2,}/', '_', $username);

        return mb_trim($username, '_.');
    }

    private function removeAccents(string $string): string
    {
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $string
        );
    }
}