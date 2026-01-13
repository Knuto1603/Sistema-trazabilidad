<?php

namespace App\Shared\Domain\Blockchain;

interface TraceableInterface
{
    /**
     * Genera un hash basado en los datos críticos de la entidad.
     */
    public function calculateLocalHash(): string;

    /**
     * Retorna el ID de la transacción en la Blockchain.
     */
    public function getBlockchainTransactionId(): ?string;

    /**
     * Indica si los datos actuales coinciden con el sello original.
     */
    public function isIntegrityValid(): bool;
}
