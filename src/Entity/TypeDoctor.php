<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TypeDoctorRepository")
 */
class TypeDoctor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="type_doctor")
     */
    private $doctors;

    public function __construct()
    {
        $this->doctors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getDoctors(): Collection
    {
        return $this->doctors;
    }

    public function addDoctor(User $doctor): self
    {
        if (!$this->doctors->contains($doctor)) {
            $this->doctors[] = $doctor;
            $doctor->addTypeDoctor($this);
        }

        return $this;
    }

    public function removeDoctor(User $doctor): self
    {
        if ($this->doctors->contains($doctor)) {
            $this->doctors->removeElement($doctor);
            $doctor->removeTypeDoctor($this);
        }

        return $this;
    }
}
