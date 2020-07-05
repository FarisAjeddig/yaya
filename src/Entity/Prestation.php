<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PrestationRepository")
 */
class Prestation
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
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="prestations")
     */
    private $doctor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Appointment", mappedBy="prestation")
     */
    private $appointments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DonationRequest", mappedBy="prestation")
     */
    private $donationRequests;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->donationRequests = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDoctor(): ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor): self
    {
        $this->doctor = $doctor;

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setPrestation($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->contains($appointment)) {
            $this->appointments->removeElement($appointment);
            // set the owning side to null (unless already changed)
            if ($appointment->getPrestation() === $this) {
                $appointment->setPrestation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DonationRequest[]
     */
    public function getDonationRequests(): Collection
    {
        return $this->donationRequests;
    }

    public function addDonationRequest(DonationRequest $donationRequest): self
    {
        if (!$this->donationRequests->contains($donationRequest)) {
            $this->donationRequests[] = $donationRequest;
            $donationRequest->setPrestation($this);
        }

        return $this;
    }

    public function removeDonationRequest(DonationRequest $donationRequest): self
    {
        if ($this->donationRequests->contains($donationRequest)) {
            $this->donationRequests->removeElement($donationRequest);
            // set the owning side to null (unless already changed)
            if ($donationRequest->getPrestation() === $this) {
                $donationRequest->setPrestation(null);
            }
        }

        return $this;
    }
}
