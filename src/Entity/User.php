<?php

namespace App\Entity;

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
     * @ORM\Column(name="type_doctor", type="string", nullable=true)
     */
    public $type_doctor;

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


    /** @ORM\Column(name="price", type="float", nullable=true) */
    public $price;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $desc;

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
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
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

    /**
     * @return bool
     */
    public function isIsDoctor(): bool
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
        // your own logic
    }
}