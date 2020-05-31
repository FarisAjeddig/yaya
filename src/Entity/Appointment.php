<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AppointmentRepository")
 */
class Appointment
{
    const STATUS_UNPAID = 'Le client n\'a pas encore entré sa carte';
    const STATUS_PAID = 'Le client a entré sa carte, en attente que le médecin propose un créneau';
    const STATUS_WAITING_FOR_PATIENT = 'Le médecin a proposé un créneau mais le patient n\'a pas encore répondu';
    const STATUS_REFUSED_BY_PATIENT = 'Le patient a refusé le créneau proposé par le médecin, on attent qu\'il en propose un nouveau';
    const STATUS_ACCEPTED_BY_PATIENT = 'Le patient et le médecin se sont mis d\'accord sur le créneau.';
    const STATUS_DONE = "Le rendez-vous a bien eu lieu, l'argent a été débloqué pour le médecin et il peut le demander dans son profil";

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phoneNumberPatient;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $emailPatient;

    /**
     * @ORM\Column(type="text")
     */
    private $schedulePatient;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $state;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $scheduleByDoctor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="appointmentAsBuyer")
     */
    private $buyer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="appointmentAsPatient")
     */
    private $patient;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="appointmentAsDoctor")
     */
    private $doctor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Prestation", inversedBy="appointments")
     */
    private $prestation;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumberPatient(): ?string
    {
        return $this->phoneNumberPatient;
    }

    public function setPhoneNumberPatient(string $phoneNumberPatient): self
    {
        $this->phoneNumberPatient = $phoneNumberPatient;

        return $this;
    }

    public function getEmailPatient(): ?string
    {
        return $this->emailPatient;
    }

    public function setEmailPatient(string $emailPatient): self
    {
        $this->emailPatient = $emailPatient;

        return $this;
    }

    public function getSchedulePatient(): ?string
    {
        return $this->schedulePatient;
    }

    public function setSchedulePatient(string $schedulePatient): self
    {
        $this->schedulePatient = $schedulePatient;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getScheduleByDoctor(): ?\DateTimeInterface
    {
        return $this->scheduleByDoctor;
    }

    public function setScheduleByDoctor(?\DateTimeInterface $scheduleByDoctor): self
    {
        $this->scheduleByDoctor = $scheduleByDoctor;

        return $this;
    }


    public function getBuyer(): ?User
    {
        return $this->buyer;
    }


    public function setBuyer(?User $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): self
    {
        $this->patient = $patient;

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

    public function getPrestation(): ?Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(?Prestation $prestation): self
    {
        $this->prestation = $prestation;

        return $this;
    }
}
