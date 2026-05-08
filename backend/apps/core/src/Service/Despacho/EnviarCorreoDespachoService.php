<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Repository\ParametroRepository;
use App\apps\core\Repository\UserSmtpConfigRepository;
use App\shared\Service\SmtpEncryptionService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class EnviarCorreoDespachoService
{
    private const ALIAS_FIRMA_NOMBRE  = 'FRNOMB';
    private const ALIAS_FIRMA_CARGO   = 'FRCARG';
    private const ALIAS_FIRMA_EMPRESA = 'FRNEMP';
    private const ALIAS_REM_NOMBRE    = 'REMNOM';
    private const ALIAS_REM_EMAIL     = 'REMAIL';
    private const ALIAS_CC_MAIL       = 'CCMAIL';

    public function __construct(
        private DespachoRepository $despachoRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private FacturaRepository $facturaRepository,
        private ParametroRepository $parametroRepository,
        private UserSmtpConfigRepository $userSmtpConfigRepository,
        private SmtpEncryptionService $encryption,
        private MailerInterface $mailer,
        private TokenStorageInterface $tokenStorage,
        #[Autowire('%env(SMTP_HOST)%')]
        private string $smtpHost,
        #[Autowire('%env(int:SMTP_PORT)%')]
        private int $smtpPort,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function preview(string $despachoUuid): array
    {
        $despacho = $this->despachoRepository->ofId($despachoUuid, true);
        $ccRaw    = $this->parametroRepository->findByAlias(self::ALIAS_CC_MAIL)?->getName() ?? '';

        return [
            'asunto'        => $this->buildAsunto($despacho),
            'cuerpo'        => $this->buildCuerpo($despacho),
            'destinatarios' => $despacho->getCliente()?->getEmailDestinatarios() ?? '',
            'cc'            => $ccRaw,
        ];
    }

    /**
     * @param string[] $archivosIds UUIDs de archivos a adjuntar
     */
    public function execute(
        string $despachoUuid,
        string $asunto,
        string $cuerpo,
        string $destinatarios,
        array $archivosIds,
    ): void {
        $despacho = $this->despachoRepository->ofId($despachoUuid, true);

        $toEmails = $this->parseDestinatarios($destinatarios);
        if (empty($toEmails)) {
            throw new \RuntimeException('No hay destinatarios válidos para enviar el correo.');
        }

        [$remitenteEmail, $remitenteNombre, $mailer] = $this->resolveRemitente();

        $ccRaw = $this->parametroRepository->findByAlias(self::ALIAS_CC_MAIL)?->getName() ?? '';

        $email = (new Email())
            ->from(new Address($remitenteEmail, $remitenteNombre))
            ->subject($asunto)
            ->text($cuerpo);

        foreach ($toEmails as $to) {
            $email->addTo($to);
        }

        foreach ($this->parseDestinatarios($ccRaw) as $cc) {
            $email->addCc($cc);
        }

        $email->addBcc(new Address($remitenteEmail, $remitenteNombre));

        if (!empty($archivosIds)) {
            $this->adjuntarArchivosSeleccionados($email, $despachoUuid, $archivosIds);
        }

        $mailer->send($email);
    }

    /**
     * Resuelve el remitente: usa SMTP personal del usuario si está configurado,
     * o el SMTP global (parámetros del sistema) como fallback.
     *
     * @return array{0: string, 1: string, 2: MailerInterface}
     */
    private function resolveRemitente(): array
    {
        $userUuid = $this->tokenStorage->getToken()?->getUserIdentifier();

        if ($userUuid !== null) {
            $smtpConfig = $this->userSmtpConfigRepository->findByUserUuid($userUuid);

            if ($smtpConfig !== null) {
                $smtpPassword = $this->encryption->decrypt($smtpConfig->getSmtpPasswordEncrypted());
                $dsn = sprintf(
                    'smtp://%s:%s@%s:%d',
                    rawurlencode($smtpConfig->getSmtpEmail()),
                    rawurlencode($smtpPassword),
                    $this->smtpHost,
                    $this->smtpPort
                );

                $transport = Transport::fromDsn($dsn);
                sodium_memzero($smtpPassword);
                $dsn = str_repeat("\0", strlen($dsn));
                unset($dsn);

                return [
                    $smtpConfig->getSmtpEmail(),
                    $smtpConfig->getSmtpEmail(),
                    new Mailer($transport),
                ];
            }
        }

        // Fallback: SMTP global configurado en parámetros del sistema
        $params = $this->parametroRepository->findValuesByAliases([
            self::ALIAS_REM_NOMBRE,
            self::ALIAS_REM_EMAIL,
        ]);

        $remitenteEmail  = $params[self::ALIAS_REM_EMAIL] ?? '';
        $remitenteNombre = $params[self::ALIAS_REM_NOMBRE] ?? 'Facturación';

        if (!$remitenteEmail) {
            throw new \RuntimeException(
                'No hay email remitente configurado. Configure SMTP personal o cree el parámetro REMAIL en Configuración > Parámetros.'
            );
        }

        return [$remitenteEmail, $remitenteNombre, $this->mailer];
    }

    private function adjuntarArchivosSeleccionados(Email $email, string $despachoUuid, array $archivosIds): void
    {
        $todosLosArchivos = $this->archivoDespachoRepository->findByDespachoUuid($despachoUuid);

        foreach ($todosLosArchivos as $archivo) {
            if (!\in_array($archivo->uuidToString(), $archivosIds, true)) {
                continue;
            }

            $path = $this->projectDir . '/public/' . $archivo->getRuta();
            if (!\file_exists($path)) {
                throw new \RuntimeException(
                    sprintf('Archivo no encontrado en disco: %s (ruta: %s)', $archivo->getNombre(), $path)
                );
            }

            $nombreOriginal = preg_replace('/^[^_]+_/', '', $archivo->getNombre()) ?? $archivo->getNombre();
            $email->attachFromPath($path, $nombreOriginal);
        }
    }

    private function buildAsunto(\App\apps\core\Entity\Despacho $despacho): string
    {
        $razonSocial = $despacho->getCliente()?->getRazonSocial() ?? 'CLIENTE';
        $fruta       = \mb_strtoupper($despacho->getFruta()?->getNombre() ?? 'FRUTA');
        $numero      = $despacho->getNumeroCliente();
        $asunto      = "{$razonSocial} - FACTURA POR SERVICIO DE MAQUILA {$fruta} - DESPACHO N° {$numero}";

        if ($despacho->getOperacion()) {
            $asunto .= ' - ' . $despacho->getOperacion()->getNombre();
        }

        return $asunto;
    }

    private function buildCuerpo(\App\apps\core\Entity\Despacho $despacho): string
    {
        $saludo  = $this->saludoPorHora();
        $numero  = $despacho->getNumeroCliente();

        $facturas      = $this->facturaRepository->findByDespachoUuid($despacho->uuidToString());
        $facturaActiva = null;
        foreach ($facturas as $f) {
            if ($f->isActive() && !$f->isAnulada()) { $facturaActiva = $f; break; }
        }
        $fecha = $facturaActiva?->getFechaEmision()?->format('d/m/Y')
            ?? $despacho->createdAt()?->format('d/m/Y')
            ?? date('d/m/Y');

        $firmaParams = $this->parametroRepository->findValuesByAliases([
            self::ALIAS_FIRMA_NOMBRE,
            self::ALIAS_FIRMA_CARGO,
            self::ALIAS_FIRMA_EMPRESA,
        ]);

        $nombre  = $firmaParams[self::ALIAS_FIRMA_NOMBRE]  ?? '';
        $cargo   = $firmaParams[self::ALIAS_FIRMA_CARGO]   ?? '';
        $empresa = $firmaParams[self::ALIAS_FIRMA_EMPRESA] ?? '';
        $firma   = implode("\n", array_filter([$nombre, $cargo, $empresa]));

        return <<<TXT
{$saludo}
Adjunto guía y factura por servicio de maquila,
correspondiente a su despacho N° {$numero} del día {$fecha}

Atte.
{$firma}
TXT;
    }

    private function saludoPorHora(): string
    {
        $hora = (int) (new \DateTime('now', new \DateTimeZone('America/Lima')))->format('H');

        return match (true) {
            $hora >= 6 && $hora < 12  => 'Buenos días',
            $hora >= 12 && $hora < 20 => 'Buenas tardes',
            default                   => 'Buenas noches',
        };
    }

    /** @return string[] */
    private function parseDestinatarios(string $raw): array
    {
        $parts = preg_split('/[;,\s]+/', $raw);

        return array_values(array_filter(
            array_map('trim', $parts),
            static fn(string $e) => filter_var($e, FILTER_VALIDATE_EMAIL) !== false
        ));
    }
}
