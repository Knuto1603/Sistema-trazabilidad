<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\ParametroRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

final readonly class EnviarCorreoDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
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

        return [
            'asunto'         => $this->buildAsunto($despacho),
            'cuerpo'         => $this->buildCuerpo($despacho),
            'destinatarios'  => $despacho->getCliente()?->getEmailDestinatarios() ?? '',
        ];
    }

    /**
     * Envía el correo con los adjuntos seleccionados.
     *
     * @param string[] $archivosIds  UUIDs de archivos a adjuntar
     */
    public function execute(
        string $despachoUuid,
        string $asunto,
        string $cuerpo,
        string $destinatarios,
        array $archivosIds,
    ): void {
        $despacho = $this->despachoRepository->ofId($despachoUuid, true);

        // Parsear destinatarios (separados por ; o coma)
        $emails = $this->parseDestinatarios($destinatarios);
        if (empty($emails)) {
            throw new \RuntimeException('No hay destinatarios válidos para enviar el correo.');
        }

        $remitenteNombre = $this->parametroRepository->findByAlias('REM_NOMBRE')?->getName() ?? 'Facturación';
        $remitenteEmail  = $this->parametroRepository->findByAlias('REM_EMAIL')?->getName() ?? '';

        if (!$remitenteEmail) {
            throw new \RuntimeException('No hay email remitente configurado. Configure el parámetro REM_EMAIL en Configuración > Parámetros.');
        }

        $email = (new Email())
            ->from(new Address($remitenteEmail, $remitenteNombre))
            ->subject($asunto)
            ->text($cuerpo);

        foreach ($emails as $to) {
            $email->addTo($to);
        }

        // Adjuntar archivos seleccionados
        if (!empty($archivosIds)) {
            $allArchivos = $this->archivoDespachoRepository->findByDespachoUuid($despachoUuid);
            foreach ($allArchivos as $archivo) {
                if (\in_array($archivo->uuidToString(), $archivosIds, true)) {
                    $path = $this->projectDir . '/public/' . $archivo->getRuta();
                    if (\file_exists($path)) {
                        $email->attachFromPath($path, $archivo->getNombre());
                    }
                }
            }
        }

        $this->mailer->send($email);
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
        $saludo    = $this->saludoPorHora();
        $numero    = $despacho->getNumeroCliente();
        $fecha     = $despacho->createdAt()?->format('d/m/Y') ?? date('d/m/Y');
        $nombre    = $this->parametroRepository->findByAlias('FRM_NOMBRE')?->getName() ?? '';
        $cargo     = $this->parametroRepository->findByAlias('FRM_CARGO')?->getName() ?? '';
        $empresa   = $this->parametroRepository->findByAlias('FRM_EMPRESA')?->getName() ?? '';

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
