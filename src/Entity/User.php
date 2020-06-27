<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="is_doctor", type="boolean", nullable=true)
     */
    public $is_doctor = false;

    /**
     * @ORM\Column(name="diploma", type="string", nullable=true)
     */
    public $diploma;

    /**
     * @Assert\Iban(
     *     message="Ce n'est pas un numéro de compte bancaire international valide (IBAN)."
     * )
     * @ORM\Column(name="bank_account_number", type="string", nullable=true)
     */
    protected $bankAccountNumber;

    /** @ORM\Column(name="phone_number", type="string", nullable=true) */
    public $phone_number;

    /** @ORM\Column(name="adress", type="string", nullable=true) */
    public $adress;


    /**
     * @var float
     *
     * @ORM\Column(name="longAdress", type="float", nullable=true)
     */
    private $longAdress;

    /**
     * @var float
     *
     * @ORM\Column(name="latAdress", type="float", nullable=true)
     */
    private $latAdress;


    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $desc;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TypeDoctor", inversedBy="doctors")
     */
    public $type_doctor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Prestation", mappedBy="doctor")
     */
    private $prestations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Appointment", mappedBy="buyer")
     */
    private $appointmentAsBuyer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Appointment", mappedBy="patient")
     */
    private $appointmentAsPatient;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Appointment", mappedBy="doctor")
     */
    private $appointmentAsDoctor;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $matriculeDoctor;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabledByAdmin = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\City", inversedBy="users")
     */
    private $city;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lowerPrice=100000;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @return float
     */
    public function getLongAdress(): float
    {
        return $this->longAdress;
    }

    /**
     * @param float $longAdress
     */
    public function setLongAdress(float $longAdress): void
    {
        $this->longAdress = $longAdress;
    }

    /**
     * @return float
     */
    public function getLatAdress(): float
    {
        return $this->latAdress;
    }

    /**
     * @param float $latAdress
     */
    public function setLatAdress(float $latAdress): void
    {
        $this->latAdress = $latAdress;
    }

    /**
     * @return mixed
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param mixed $desc
     */
    public function setDesc($desc): void
    {
        $this->desc = $desc;
    }

    /**
     * @return mixed
     */
    public function getAdress()
    {
        return $this->adress;
    }

    /**
     * @param mixed $adress
     */
    public function setAdress($adress): void
    {
        $this->adress = $adress;
    }

    /**
     * @return mixed
     */
    public function getBankAccountNumber()
    {
        return $this->bankAccountNumber;
    }

    /**
     * @param mixed $bankAccountNumber
     */
    public function setBankAccountNumber($bankAccountNumber): void
    {
        $this->bankAccountNumber = $bankAccountNumber;
    }

    /**
     * @return mixed
     */
    public function getDiploma()
    {
        return $this->diploma;
    }

    /**
     * @param mixed $diploma
     */
    public function setDiploma($diploma): void
    {
        $this->diploma = $diploma;
    }

    /**
     * @return mixed
     */
    public function getTypeDoctor()
    {
        return $this->type_doctor;
    }

    /**
     * @param mixed $type_doctor
     */
    public function setTypeDoctor($type_doctor): void
    {
        $this->type_doctor = $type_doctor;
    }

    public function clearTypeDoctor(){
        $this->type_doctor->clear();
    }

    /**
     * @return bool
     */
    public function getIsDoctor(): bool
    {
        return $this->is_doctor;
    }

    /**
     * @param bool $is_doctor
     */
    public function setIsDoctor(bool $is_doctor): void
    {
        $this->is_doctor = $is_doctor;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param mixed $phone_number
     */
    public function setPhoneNumber($phone_number): void
    {
        $this->phone_number = $phone_number;
    }


    public function __construct()
    {
        parent::__construct();
        $this->type_doctor = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->appointmentAsBuyer = new ArrayCollection();
        $this->appointmentAsPatient = new ArrayCollection();
        $this->appointmentAsDoctor = new ArrayCollection();
        // your own logic
    }

    public function addTypeDoctor(TypeDoctor $typeDoctor): self
    {
        if (!$this->type_doctor->contains($typeDoctor)) {
            $this->type_doctor[] = $typeDoctor;
            $typeDoctor->addDoctor($this);
        }

        return $this;
    }

    public function removeTypeDoctor(TypeDoctor $typeDoctor): self
    {
        if ($this->type_doctor->contains($typeDoctor)) {
            $this->type_doctor->removeElement($typeDoctor);
            // set the owning side to null (unless already changed)
            if ($typeDoctor->getDoctors() === $this) {
//                $typeDoctor->setDoctors(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Prestation[]
     */
    public function getPrestations(): Collection
    {
        return $this->prestations;
    }

    public function addPrestation(Prestation $prestation): self
    {
        if (!$this->prestations->contains($prestation)) {
            $this->prestations[] = $prestation;
            $prestation->setDoctor($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): self
    {
        if ($this->prestations->contains($prestation)) {
            $this->prestations->removeElement($prestation);
            // set the owning side to null (unless already changed)
            if ($prestation->getDoctor() === $this) {
                $prestation->setDoctor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointmentAsBuyer(): Collection
    {
        return $this->appointmentAsBuyer;
    }

    public function addAppointmentAsBuyer(Appointment $appointmentAsBuyer): self
    {
        if (!$this->appointmentAsBuyer->contains($appointmentAsBuyer)) {
            $this->appointmentAsBuyer[] = $appointmentAsBuyer;
            $appointmentAsBuyer->setBuyer($this);
        }

        return $this;
    }

    public function removeAppointmentAsBuyer(Appointment $appointmentAsBuyer): self
    {
        if ($this->appointmentAsBuyer->contains($appointmentAsBuyer)) {
            $this->appointmentAsBuyer->removeElement($appointmentAsBuyer);
            // set the owning side to null (unless already changed)
            if ($appointmentAsBuyer->getBuyer() === $this) {
                $appointmentAsBuyer->setBuyer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointmentAsPatient(): Collection
    {
        return $this->appointmentAsPatient;
    }

    public function addAppointmentAsPatient(Appointment $appointmentAsPatient): self
    {
        if (!$this->appointmentAsPatient->contains($appointmentAsPatient)) {
            $this->appointmentAsPatient[] = $appointmentAsPatient;
            $appointmentAsPatient->setPatient($this);
        }

        return $this;
    }

    public function removeAppointmentAsPatient(Appointment $appointmentAsPatient): self
    {
        if ($this->appointmentAsPatient->contains($appointmentAsPatient)) {
            $this->appointmentAsPatient->removeElement($appointmentAsPatient);
            // set the owning side to null (unless already changed)
            if ($appointmentAsPatient->getPatient() === $this) {
                $appointmentAsPatient->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Appointment[]
     */
    public function getAppointmentAsDoctor(): Collection
    {
        return $this->appointmentAsDoctor;
    }

    public function addAppointmentAsDoctor(Appointment $appointmentAsDoctor): self
    {
        if (!$this->appointmentAsDoctor->contains($appointmentAsDoctor)) {
            $this->appointmentAsDoctor[] = $appointmentAsDoctor;
            $appointmentAsDoctor->setDoctor($this);
        }

        return $this;
    }

    public function removeAppointmentAsDoctor(Appointment $appointmentAsDoctor): self
    {
        if ($this->appointmentAsDoctor->contains($appointmentAsDoctor)) {
            $this->appointmentAsDoctor->removeElement($appointmentAsDoctor);
            // set the owning side to null (unless already changed)
            if ($appointmentAsDoctor->getDoctor() === $this) {
                $appointmentAsDoctor->setDoctor(null);
            }
        }

        return $this;
    }

    public function getMatriculeDoctor(): ?string
    {
        return $this->matriculeDoctor;
    }

    public function setMatriculeDoctor(?string $matriculeDoctor): self
    {
        $this->matriculeDoctor = $matriculeDoctor;

        return $this;
    }

    public function getEnabledByAdmin(): ?bool
    {
        return $this->enabledByAdmin;
    }

    public function setEnabledByAdmin(bool $enabledByAdmin): self
    {
        $this->enabledByAdmin = $enabledByAdmin;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getLowerPrice(): ?float
    {
        return $this->lowerPrice;
    }

    public function setLowerPrice(?float $lowerPrice): self
    {
        $this->lowerPrice = $lowerPrice;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

}