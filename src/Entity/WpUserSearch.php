<?php

namespace App\Entity;

use App\Repository\WpUserRepository;
use Doctrine\ORM\Mapping as ORM;

class WpUserSearch
{

    private $todos;
    private $dataInicio;
    private $dataFim;
    private $pais;
    private $location;
    
    public function getTodos() {
        return $this->todos;
    }

    public function getDataInicio() {
        return $this->dataInicio;
    }

    public function getDataFim() {
        return $this->dataFim;
    }

    public function getPais() {
        return $this->pais;
    }

    public function setTodos($todos): void {
        $this->todos = $todos;
    }

    public function setDataInicio($dataInicio): void {
        $this->dataInicio = $dataInicio;
    }

    public function setDataFim($dataFim): void {
        $this->dataFim = $dataFim;
    }

    public function setPais($pais): void {
        $this->pais = $pais;
    }


    public function getLocation() {
        return $this->location;
    }

    public function setLocation($location): void {
        $this->location = $location;
    }


    
    
    
    
}
