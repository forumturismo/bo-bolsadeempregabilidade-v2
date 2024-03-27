<?php

namespace App\Entity;

use App\Repository\WpUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WpUserRepository::class)
 * @ORM\Table(name="wp_users")
 */
class WpUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_nicename;

    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_login;

    private $first_name;
    private $last_name;
    
    private $user_resumes_count;
    
    private $user_resumes_last_updated;
    
    
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $user_email;

    /**
     * @ORM\Column(type="datetime")
     */
    private $user_registered;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $display_name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserNicename(): ?string
    {
        return $this->user_nicename;
    }

    public function setUserNicename(?string $user_nicename): self
    {
        $this->user_nicename = $user_nicename;

        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function setUserEmail(string $user_email): self
    {
        $this->user_email = $user_email;

        return $this;
    }

    public function getUserRegistered(): ?\DateTimeInterface
    {
        return $this->user_registered;
    }

    public function setUserRegistered(\DateTimeInterface $user_registered): self
    {
        $this->user_registered = $user_registered;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->display_name;
    }

    public function setDisplayName(string $display_name): self
    {
        $this->display_name = $display_name;

        return $this;
    }
    
    
    
    public function getUser_nicename() {
        return $this->user_nicename;
    }

    public function getUser_login() {
        return $this->user_login;
    }

    public function getUser_resumes_count() {
        return $this->user_resumes_count;
    }

    public function getUser_resumes_last_updated() {
        return $this->user_resumes_last_updated;
    }

    public function getUser_email() {
        return $this->user_email;
    }

    public function getUser_registered() {
        return $this->user_registered;
    }

    public function getDisplay_name() {
        return $this->display_name;
    }

    public function setUser_nicename($user_nicename): void {
        $this->user_nicename = $user_nicename;
    }

    public function setUser_login($user_login): void {
        $this->user_login = $user_login;
    }

    public function setUser_resumes_count($user_resumes_count): void {
        $this->user_resumes_count = $user_resumes_count;
    }

    public function setUser_resumes_last_updated($user_resumes_last_updated): void {
        $this->user_resumes_last_updated = $user_resumes_last_updated;
    }

    public function setUser_email($user_email): void {
        $this->user_email = $user_email;
    }

    public function setUser_registered($user_registered): void {
        $this->user_registered = $user_registered;
    }

    public function setDisplay_name($display_name): void {
        $this->display_name = $display_name;
    }


    
    public function getFirst_name() {
        return $this->first_name;
    }

    public function getLast_name() {
        return $this->last_name;
    }

    public function setFirst_name($first_name): void {
        $this->first_name = $first_name;
    }

    public function setLast_name($last_name): void {
        $this->last_name = $last_name;
    }


    
    
}
