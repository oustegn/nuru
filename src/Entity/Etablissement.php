<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EtablissementRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
#[ORM\Table(name: 'etablissement')]
class Etablissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $nom;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $creeLe;

    /** @var Collection<int, Formation> */
    #[ORM\OneToMany(mappedBy: 'etablissement', targetEntity: Formation::class)]
    private Collection $formations;

    public function __construct(string $slug, string $nom)
    {
        $this->formations = new ArrayCollection();
        $this->setSlug($slug);
        $this->setNom($nom);
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->assertNonEmpty($nom, 'Le nom');
        $this->nom = $nom;

        return $this;
    }

    public function getCreeLe(): DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function getAdresse(): ?string { return $this->adresse; }
    public function getVille(): ?string { return $this->ville; }
    public function getSiteWeb(): ?string { return $this->siteWeb; }

    public function setAdresse(?string $value): self
    {
        $value = trim($value ?? '');
        if (mb_strlen($value) > 1000) { throw new InvalidArgumentException('Adresse trop longue.'); }
        $this->adresse = $value === '' ? null : $value;
        return $this;
    }

    public function setVille(?string $value): self
    {
        $value = trim($value ?? '');
        if (mb_strlen($value) > 100) { throw new InvalidArgumentException('Ville trop longue.'); }
        $this->ville = $value === '' ? null : $value;
        return $this;
    }

    public function setSiteWeb(?string $value): self
    {
        $value = trim($value ?? '');
        if ($value !== '' && (mb_strlen($value) > 255 || !filter_var($value, FILTER_VALIDATE_URL)
            || !in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true))) {
            throw new InvalidArgumentException('Site web invalide.');
        }
        $this->siteWeb = $value === '' ? null : $value;
        return $this;
    }

    /** @return Collection<int, Formation> */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): self
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
        }

        if ($formation->getEtablissement() !== $this) {
            $formation->setEtablissement($this);
        }

        return $this;
    }

    /**
     * Retire seulement le côté inverse après une réaffectation de la formation.
     * Une formation ne peut pas être détachée d'un établissement, sa relation
     * propriétaire étant obligatoire.
     */
    public function removeFormation(Formation $formation): self
    {
        if ($formation->getEtablissement() === $this) {
            throw new LogicException('Une formation doit rester rattachée à un établissement.');
        }

        $this->formations->removeElement($formation);

        return $this;
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('%s ne peut pas être vide.', $field));
        }
    }
}
