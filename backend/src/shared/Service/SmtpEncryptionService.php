<?php

namespace App\shared\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Cifra y descifra contraseñas SMTP usando libsodium (XSalsa20-Poly1305).
 * La clave se almacena en APP_SMTP_ENCRYPTION_KEY (base64 de 32 bytes).
 */
final readonly class SmtpEncryptionService
{
    private string $key;

    public function __construct(
        #[Autowire('%env(APP_SMTP_ENCRYPTION_KEY)%')]
        string $base64Key,
    ) {
        $decoded = base64_decode($base64Key, true);

        if ($decoded === false || \strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException(
                'APP_SMTP_ENCRYPTION_KEY inválida. Genera una con: php -r "echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));"'
            );
        }

        $this->key = $decoded;
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);
        sodium_memzero($plaintext);

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || \strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Formato de contraseña SMTP inválido.');
        }

        $nonce      = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext  = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if ($plaintext === false) {
            throw new \RuntimeException('No se pudo descifrar la contraseña SMTP. Verifique que APP_SMTP_ENCRYPTION_KEY sea la correcta.');
        }

        return $plaintext;
    }
}
