<?php

namespace App\shared\Symfony;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use function Symfony\Component\String\u;

abstract class BaseKernel extends Kernel
{
    use MicroKernelTrait;

    protected ?string $varName = null;

    public function getCacheDir(): string
    {
        if ($this->appVarName()) {
            return $this->appRootDir().'var/cache/'.$this->appVarName().'_'.$this->environment;
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if ($this->appVarName()) {
            return $this->appRootDir().'var/log/'.$this->appVarName();
        }

        return parent::getLogDir();
    }

    protected function appVarName(): ?string
    {
        if (!$this->varName) {
            $path = $this->getProjectDir();
            $position = mb_strpos($path, 'apps') + 5;
            $this->varName = u(mb_substr($path, $position))->snake()->toString();
        }

        return $this->varName;
    }

    protected function appRootDir(): string
    {
        return \dirname(__DIR__).'/../../';
    }
}
