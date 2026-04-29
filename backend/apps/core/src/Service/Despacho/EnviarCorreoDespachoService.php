<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Repository\ParametroRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

final readonly class EnviarCorreoDespachoService
{
    // Aliases de parámetros (máx 6 caracteres)
    private const ALIAS_FIRMA_NOMBRE  = 'FRNOMB';
    private const ALIAS_FIRMA_CARGO   = 'FRCARG';
    private const ALIAS_FIRMA_EMPRESA = 'FRNEMP';
    private const ALIAS_REM_NOMBRE    = 'REMNOM';
    private const ALIAS_REM_EMAIL     = 'REMAIL';
    private const ALIAS_CC_MAIL       = 'CCMAIL'; // copia fija a correos de la empresa

    public function __construct(
        private DespachoRepository $despachoRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private FacturaRepository $facturaRepository,
        private ParametroRepository $parametroRepository,
        private MailerInterface $mailer,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Genera la vista previa del asunto, cuerpo y destinatarios sin enviar.
     */
    public function preview(string $despachoUuid): array
    {
        $despacho = $this->despachoRepository->ofId($despachoUuid, true);

        $ccRaw = $this->parametroRepository->findByAlias(self::ALIAS_CC_MAIL)?->getName() ?? '';

        return [
            'asunto'        => $this->buildAsunto($despacho),
            'cuerpo'        => $this->buildCuerpo($despacho),
            'destinatarios' => $despacho->getCliente()?->getEmailDestinatarios() ?? '',
            'cc'            => $ccRaw,
        ];
    }

    /**
     * Envía el correo con los adjuntos seleccionados.
     *
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

        $params = $this->parametroRepository->findValuesByAliases([
            self::ALIAS_REM_NOMBRE,
            self::ALIAS_REM_EMAIL,
            self::ALIAS_CC_MAIL,
        ]);

        $remitenteNombre = $params[self::ALIAS_REM_NOMBRE] ?? 'Facturación';
        $remitenteEmail  = $params[self::ALIAS_REM_EMAIL] ?? '';

        if (!$remitenteEmail) {
            throw new \RuntimeException('No hay email remitente configurado. Cree el parámetro con alias REMAIL en Configuración > Parámetros.');
        }

        $email = (new Email())
            ->from(new Address($remitenteEmail, $remitenteNombre))
            ->subject($asunto)
            ->text($cuerpo);

        foreach ($toEmails as $to) {
            $email->addTo($to);
        }

        foreach ($this->parseDestinatarios($params[self::ALIAS_CC_MAIL] ?? '') as $cc) {
            $email->addCc($cc);
        }

        $email->addBcc(new Address($remitenteEmail, $remitenteNombre));

        if (!empty($archivosIds)) {
            $this->adjuntarArchivosSeleccionados($email, $despachoUuid, $archivosIds);
        }

        $this->mailer->send($email);
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

        $facturas = $this->facturaRepository->findByDespachoUuid($despacho->uuidToString());
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

        $firma = implode("\n", array_filter([$nombre, $cargo, $empresa]));

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
