<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ModuleFormationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ModuleFormationRepository::class)]
#[ORM\Table(name: 'module_formation')]
#[ORM\UniqueConstraint(name: 'uniq_module_formation_position', columns: ['formation_id', 'position'])]
class ModuleFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $titre;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    public function __construct(string $titre, int $position)
    {
        $this->setTitre($titre);
        $this->setPosition($position);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        if (trim($titre) === '') {
            throw new InvalidArgumentException('Le titre ne peut pas être vide.');
        }

        $this->titre = $titre;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        if ($position < 1) {
            throw new InvalidArgumentException('La position doit être supérieure ou égale à 1.');
        }

        $this->position = $position;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        if ($this->formation === $formation) {
            return $this;
        }

        $precedente = $this->formation;
        $this->formation = $formation;

        if ($precedente !== null) {
            $precedente->removeModule($this);
        }

        if ($formation !== null) {
            $formation->addModule($this);
        }

        return $this;
    }
}
