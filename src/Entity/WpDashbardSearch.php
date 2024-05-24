<?php

namespace App\Entity;

use App\Repository\WpUserRepository;
use Doctrine\ORM\Mapping as ORM;

class WpDashboardSearch
{

    
    private $dataInicio;
    private $dataFim;
    
    public function getDataInicio() {
        return $this->dataInicio;
    }

    public function getDataFim() {
        return $this->dataFim;
    }

    public function setDataInicio($dataInicio): void {
        $this->dataInicio = $dataInicio;
    }

    public function setDataFim($dataFim): void {
        $this->dataFim = $dataFim;
    }
    
    
}
