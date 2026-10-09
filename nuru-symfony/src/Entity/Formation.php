<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FormationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formation')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $titre;

    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'etablissement_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Etablissement $etablissement;

    #[ORM\Column(length: 64)]
    private string $domaine = 'Numérique';

    #[ORM\Column(length: 32)]
    private string $programme = 'Certification';

    #[ORM\Column(length: 32)]
    private string $modalite = 'En ligne';

    #[ORM\Column(length: 64)]
    private string $duree = 'A définir';

    #[ORM\Column(type: Types::INTEGER)]
    private int $heures = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $prix = 0;

    #[ORM\Column(length: 512)]
    private string $image = '/manus-storage/async-images/lOTTiaptubHNn5aRHM2kRv/image-1.webp';

    #[ORM\Column(length: 255)]
    private string $formateur = 'A définir';

    #[ORM\Column(length: 64)]
    private string $niveau = 'Débutant';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    /** @var array<mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $objectifs = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $miseEnAvant = false;

    #[ORM\Column(length: 64)]
    private string $statut = 'Accès à votre rythme';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dates = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $places = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $creeLe;

    /** @var Collection<int, ModuleFormation> */
    #[ORM\OneToMany(
        mappedBy: 'formation',
        targetEntity: ModuleFormation::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $modules;

    public function __construct(string $slug, string $titre, Etablissement $etablissement)
    {
        $this->modules = new ArrayCollection();
        $this->setSlug($slug);
        $this->setTitre($titre);
        $this->etablissement = $etablissement;
        $etablissement->addFormation($this);
        $this->creeLe = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->assertNonEmpty($slug, 'Le slug');
        $this->slug = $slug;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->assertNonEmpty($titre, 'Le titre');
        $this->titre = $titre;

        return $this;
    }

    public function getEtablissement(): Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(Etablissement $etablissement): self
    {
        if (isset($this->etablissement) && $this->etablissement === $etablissement) {
            $etablissement->addFormation($this);

            return $this;
        }

        $precedent = isset($this->etablissement) ? $this->etablissement : null;
        $this->etablissement = $etablissement;

        if ($precedent !== null) {
            $precedent->removeFormation($this);
        }

        $etablissement->addFormation($this);

        return $this;
    }

    public function getDomaine(): string
    {
        return $this->domaine;
    }

    public function setDomaine(string $domaine): self
    {
        $this->domaine = $domaine;

        return $this;
    }

    public function getProgramme(): string
    {
        return $this->programme;
    }

    public function setProgramme(string $programme): self
    {
        $this->programme = $programme;

        return $this;
    }

    public function getModalite(): string
    {
        return $this->modalite;
    }

    public function setModalite(string $modalite): self
    {
        $this->modalite = $modalite;

        return $this;
    }

    public function getDuree(): string
    {
        return $this->duree;
    }

    public function setDuree(string $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getHeures(): int
    {
        return $this->heures;
    }

    public function setHeures(int $heures): self
    {
        $this->assertNonNegative($heures, 'Le nombre d’heures');
        $this->heures = $heures;

        return $this;
    }

    public function getPrix(): int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): self
    {
        $this->assertNonNegative($prix, 'Le prix');
        $this->prix = $prix;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getFormateur(): string
    {
        return $this->formateur;
    }

    public function setFormateur(string $formateur): self
    {
        $this->formateur = $formateur;

        return $this;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /** @return array<mixed> */
    public function getObjectifs(): array
    {
        return $this->objectifs;
    }

    /** @param array<mixed> $objectifs */
    public function setObjectifs(array $objectifs): self
    {
        $this->objectifs = $objectifs;

        return $this;
    }

    public function isMiseEnAvant(): bool
    {
        return $this->miseEnAvant;
    }

    public function setMiseEnAvant(bool $miseEnAvant): self
    {
        $this->miseEnAvant = $miseEnAvant;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getDates(): ?string
    {
        return $this->dates;
    }

    public function setDates(?string $dates): self
    {
        $this->dates = $dates;

        return $this;
    }

    public function getPlaces(): ?int
    {
        return $this->places;
    }

    public function setPlaces(?int $places): self
    {
        if ($places !== null) {
            $this->assertNonNegative($places, 'Le nombre de places');
        }

        $this->places = $places;

        return $this;
    }

    public function getCreeLe(): DateTimeImmutable
    {
        return $this->creeLe;
    }

    /** @return Collection<int, ModuleFormation> */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(ModuleFormation $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
        }

        if ($module->getFormation() !== $this) {
            $module->setFormation($this);
        }

        return $this;
    }

    public function removeModule(ModuleFormation $module): self
    {
        if ($this->modules->removeElement($module) && $module->getFormation() === $this) {
            $module->setFormation(null);
        }

        return $this;
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s ne peut pas être vide.', $field));
        }
    }

    private function assertNonNegative(int $value, string $field): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('%s ne peut pas être négatif.', $field));
        }
    }
}
