<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UnexpectedValueException;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public const ROLE_APPRENANT = 'ROLE_APPRENANT';
    public const ROLE_FORMATEUR = 'ROLE_FORMATEUR';
    public const ROLE_ETABLISSEMENT = 'ROLE_ETABLISSEMENT';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /** @var list<string> */
    private const ALLOWED_ROLES = [
        self::ROLE_APPRENANT,
        self::ROLE_FORMATEUR,
        self::ROLE_ETABLISSEMENT,
        self::ROLE_ADMIN,
    ];

    private const TYPE_INSCRIPTION_INTERNE = 'interne';
    private const TYPE_INSCRIPTION_APPRENANT = 'apprenant';
    private const TYPE_INSCRIPTION_FORMATEUR = 'formateur';
    private const TYPE_INSCRIPTION_ETABLISSEMENT = 'etablissement';

    private const STATUT_VALIDATION_APPROUVE = 'approuve';
    private const STATUT_VALIDATION_ATTENTE = 'attente';
    private const STATUT_VALIDATION_REFUSE = 'refuse';
    private const STATUT_VALIDATION_DISPENSE = 'dispense';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 255)]
    private string $password;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [self::ROLE_APPRENANT];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    #[ORM\Column(name: 'type_inscription', length: 20, options: ['default' => self::TYPE_INSCRIPTION_INTERNE])]
    private string $typeInscription = self::TYPE_INSCRIPTION_INTERNE;

    #[ORM\Column(name: 'statut_validation', length: 20, options: ['default' => self::STATUT_VALIDATION_APPROUVE])]
    private string $statutValidation = self::STATUT_VALIDATION_APPROUVE;

    #[ORM\Column(name: 'email_verifie_le', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $emailVerifieLe = null;

    #[ORM\Column(name: 'decision_le', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $decisionLe = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'decision_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $decisionPar = null;

    #[ORM\Column(name: 'motif_refus', type: Types::TEXT, nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $presentation = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\ManyToOne(targetEntity: Etablissement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Etablissement $etablissement = null;

    #[ORM\Column(name: 'verification_token_hash', length: 64, unique: true, nullable: true)]
    private ?string $verificationTokenHash = null;

    #[ORM\Column(name: 'verification_expire_le', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $verificationExpireLe = null;

    #[ORM\Column(name: 'verification_envoye_le', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $verificationEnvoyeLe = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $creeLe;

    /** @var Collection<int, Formation> */
    #[ORM\OneToMany(mappedBy: 'formateurUtilisateur', targetEntity: Formation::class)]
    private Collection $formationsEnseignees;

    public function __construct(string $email, string $prenom, string $nom)
    {
        $this->formationsEnseignees = new ArrayCollection();
        $this->setEmail($email);
        $this->setPrenom($prenom);
        $this->setNom($nom);
        $this->creeLe = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $email = self::normalizeEmail($email);
        if ($email === '' || mb_strlen($email, 'UTF-8') > 180 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('L’adresse e-mail est invalide ou dépasse 180 caractères.');
        }

        $this->email = $email;

        return $this;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $this->normalizeRequiredName($prenom, 'Le prénom');

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $this->normalizeRequiredName($nom, 'Le nom');

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Accepte uniquement une empreinte reconnue par l’API native de PHP.
     * Le mot de passe en clair ne doit jamais être conservé dans l’entité.
     */
    public function setPassword(string $password): self
    {
        $passwordInfo = password_get_info($password);
        if (($passwordInfo['algo'] ?? null) === null || ($passwordInfo['algoName'] ?? 'unknown') === 'unknown') {
            throw new InvalidArgumentException('Le mot de passe doit être une empreinte reconnue.');
        }
        if (strlen($password) > 255) {
            throw new InvalidArgumentException('L’empreinte du mot de passe dépasse 255 caractères.');
        }

        $this->password = $password;

        return $this;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->roles as $role) {
            if (is_string($role) && self::isAllowedRole($role)) {
                $roles[] = $role;
            }
        }
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param array<mixed> $roles */
    public function setRoles(array $roles): self
    {
        if ($roles === []) {
            throw new InvalidArgumentException('Au moins un rôle applicatif est requis.');
        }

        $normalizedRoles = [];
        foreach ($roles as $role) {
            if (!is_string($role) || !self::isAllowedRole($role)) {
                throw new InvalidArgumentException('Un rôle utilisateur est invalide.');
            }
            $normalizedRoles[$role] = true;
        }

        $this->roles = array_values(array_keys($normalizedRoles));

        return $this;
    }

    public static function isAllowedRole(string $role): bool
    {
        return in_array($role, self::ALLOWED_ROLES, true);
    }

    /** @return list<string> */
    public static function allowedRoles(): array
    {
        return self::ALLOWED_ROLES;
    }

    public function isActif(): bool
    {
        return $this->actif
            && (!$this->isInscriptionPublique()
                || ($this->isEmailVerifie()
                    && (!$this->requiresAdminApproval() || $this->statutValidation === self::STATUT_VALIDATION_APPROUVE)));
    }

    public function setActif(bool $actif): self
    {
        // L’état brut est conservé pour les comptes internes ; isActif() applique toujours les garde-fous publics.
        $this->actif = $actif;

        return $this;
    }

    public function getTypeInscription(): string
    {
        return $this->typeInscription;
    }

    public function getStatutValidation(): string
    {
        return $this->statutValidation;
    }

    public function getEmailVerifieLe(): ?DateTimeImmutable
    {
        return $this->emailVerifieLe;
    }

    public function getDecisionLe(): ?DateTimeImmutable
    {
        return $this->decisionLe;
    }

    public function getDecisionPar(): ?User
    {
        return $this->decisionPar;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motifRefus;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): self
    {
        $this->specialite = $this->normalizeOptionalText($specialite, 180, 'La spécialité');

        return $this;
    }

    public function getPresentation(): ?string
    {
        return $this->presentation;
    }

    public function setPresentation(?string $presentation): self
    {
        $this->presentation = $this->normalizeOptionalText($presentation, 4000, 'La présentation');

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $this->normalizeOptionalText($telephone, 30, 'Le téléphone');

        return $this;
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getVerificationTokenHash(): ?string
    {
        return $this->verificationTokenHash;
    }

    public function getVerificationExpireLe(): ?DateTimeImmutable
    {
        return $this->verificationExpireLe;
    }

    public function getVerificationEnvoyeLe(): ?DateTimeImmutable
    {
        return $this->verificationEnvoyeLe;
    }

    public function beginPublicRegistration(string $type): void
    {
        if ($this->typeInscription !== self::TYPE_INSCRIPTION_INTERNE) {
            throw new InvalidArgumentException('Une inscription publique ne peut être initiée qu’une seule fois.');
        }

        if (!in_array($type, self::publicRegistrationTypes(), true)) {
            throw new InvalidArgumentException('Le type d’inscription publique est invalide.');
        }

        $this->typeInscription = $type;
        $this->roles = [match ($type) {
            self::TYPE_INSCRIPTION_APPRENANT => self::ROLE_APPRENANT,
            self::TYPE_INSCRIPTION_FORMATEUR => self::ROLE_FORMATEUR,
            self::TYPE_INSCRIPTION_ETABLISSEMENT => self::ROLE_ETABLISSEMENT,
        }];
        $this->actif = false;
        $this->statutValidation = $type === self::TYPE_INSCRIPTION_APPRENANT
            ? self::STATUT_VALIDATION_DISPENSE
            : self::STATUT_VALIDATION_ATTENTE;
    }

    public function isInscriptionPublique(): bool
    {
        return in_array($this->typeInscription, self::publicRegistrationTypes(), true);
    }

    public function isEmailVerifie(): bool
    {
        return $this->emailVerifieLe !== null;
    }

    public function requiresAdminApproval(): bool
    {
        return in_array($this->typeInscription, [
            self::TYPE_INSCRIPTION_FORMATEUR,
            self::TYPE_INSCRIPTION_ETABLISSEMENT,
        ], true);
    }

    public function markEmailVerified(): void
    {
        $this->emailVerifieLe = new DateTimeImmutable();
        $this->verificationTokenHash = null;
        $this->verificationExpireLe = null;
    }

    public function approveRegistration(User $admin): void
    {
        $this->assertPendingPublicApprovalAction($admin);

        $this->statutValidation = self::STATUT_VALIDATION_APPROUVE;
        $this->decisionLe = new DateTimeImmutable();
        $this->decisionPar = $admin;
        $this->motifRefus = null;
    }

    public function rejectRegistration(User $admin, string $reason): void
    {
        $this->assertPendingPublicApprovalAction($admin);

        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason, 'UTF-8') > 2000) {
            throw new InvalidArgumentException('Le motif de refus est requis et doit contenir au plus 2 000 caractères.');
        }

        $this->statutValidation = self::STATUT_VALIDATION_REFUSE;
        $this->actif = false;
        $this->decisionLe = new DateTimeImmutable();
        $this->decisionPar = $admin;
        $this->motifRefus = $reason;
        $this->verificationTokenHash = null;
        $this->verificationExpireLe = null;
    }

    public function syncRegistrationActivation(): void
    {
        if (!$this->isInscriptionPublique()) {
            return;
        }

        $this->actif = $this->isEmailVerifie()
            && (!$this->requiresAdminApproval() || $this->statutValidation === self::STATUT_VALIDATION_APPROUVE);
    }

    public function setVerificationToken(string $hash, DateTimeImmutable $expires, DateTimeImmutable $sent): void
    {
        if (!$this->isInscriptionPublique()) {
            throw new InvalidArgumentException('Un jeton de vérification est réservé aux inscriptions publiques.');
        }
        if ($this->statutValidation === self::STATUT_VALIDATION_REFUSE) {
            throw new InvalidArgumentException('Une inscription refusée ne peut pas recevoir de jeton de vérification.');
        }
        if ($this->isEmailVerifie()) {
            throw new InvalidArgumentException('L’adresse e-mail est déjà vérifiée.');
        }
        if (preg_match('/\A[a-f0-9]{64}\z/iD', $hash) !== 1) {
            throw new InvalidArgumentException('L’empreinte du jeton de vérification doit être un hexadécimal de 64 caractères.');
        }

        $this->verificationTokenHash = null;
        $this->verificationExpireLe = null;
        $this->verificationEnvoyeLe = null;
        $this->verificationTokenHash = $hash;
        $this->verificationExpireLe = $expires;
        $this->verificationEnvoyeLe = $sent;
    }

    public function getCreeLe(): DateTimeImmutable
    {
        return $this->creeLe;
    }

    /** @return Collection<int, Formation> */
    public function getFormationsEnseignees(): Collection
    {
        return $this->formationsEnseignees;
    }

    public function addFormationEnseignee(Formation $formation): self
    {
        if (!$this->formationsEnseignees->contains($formation)) {
            $this->formationsEnseignees->add($formation);
        }

        if ($formation->getFormateurUtilisateur() !== $this) {
            $formation->setFormateurUtilisateur($this);
        }

        return $this;
    }

    public function removeFormationEnseignee(Formation $formation): self
    {
        if ($this->formationsEnseignees->removeElement($formation) && $formation->getFormateurUtilisateur() === $this) {
            $formation->setFormateurUtilisateur(null);
        }

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Aucun mot de passe en clair ni autre secret transitoire n’est stocké ici.
    }

    public function isEqualTo(UserInterface $user): bool
    {
        // Appelé par Symfony au rafraîchissement du compte à chaque requête privée.
        return $user instanceof self
            && $user->isActif()
            && $this->id === $user->getId()
            && $this->email === $user->getEmail()
            && $this->getRoles() === $user->getRoles()
            && $this->actif === $user->actif
            && $this->isActif() === $user->isActif()
            && hash_equals($this->password, $user->getPassword());
    }

    /**
     * Ne conserve en session que les scalaires d’identité, l’empreinte déjà hachée
     * et l’état minimal nécessaire au calcul de l’activation effective.
     * Les associations Doctrine, les décisions et les jetons n’en font jamais partie.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'password' => $this->password,
            'roles' => $this->roles,
            'actif' => $this->actif,
            'creeLe' => $this->creeLe,
            'typeInscription' => $this->typeInscription,
            'statutValidation' => $this->statutValidation,
            'emailVerifieLe' => $this->emailVerifieLe,
        ];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        if (!isset($data['email'], $data['prenom'], $data['nom'], $data['password'], $data['roles'], $data['actif'], $data['creeLe'])
            || !is_string($data['email'])
            || !is_string($data['prenom'])
            || !is_string($data['nom'])
            || !is_string($data['password'])
            || !is_array($data['roles'])
            || !is_bool($data['actif'])
            || !$data['creeLe'] instanceof DateTimeImmutable
            || (!array_key_exists('id', $data) || ($data['id'] !== null && !is_int($data['id'])))
        ) {
            throw new UnexpectedValueException('La session utilisateur est invalide.');
        }

        $typeInscription = $data['typeInscription'] ?? self::TYPE_INSCRIPTION_INTERNE;
        $statutValidation = $data['statutValidation'] ?? self::STATUT_VALIDATION_APPROUVE;
        $emailVerifieLe = $data['emailVerifieLe'] ?? null;
        if (!is_string($typeInscription)
            || !self::isKnownRegistrationType($typeInscription)
            || !is_string($statutValidation)
            || !self::isKnownValidationStatus($statutValidation)
            || ($emailVerifieLe !== null && !$emailVerifieLe instanceof DateTimeImmutable)
        ) {
            throw new UnexpectedValueException('L’état d’inscription de la session utilisateur est invalide.');
        }

        $this->id = $data['id'];
        $this->setEmail($data['email']);
        $this->setPrenom($data['prenom']);
        $this->setNom($data['nom']);
        $this->setPassword($data['password']);
        $this->setRoles($data['roles']);
        $this->actif = $data['actif'];
        $this->creeLe = $data['creeLe'];
        $this->typeInscription = $typeInscription;
        $this->statutValidation = $statutValidation;
        $this->emailVerifieLe = $emailVerifieLe;
        $this->decisionLe = null;
        $this->decisionPar = null;
        $this->motifRefus = null;
        $this->specialite = null;
        $this->presentation = null;
        $this->telephone = null;
        $this->etablissement = null;
        $this->verificationTokenHash = null;
        $this->verificationExpireLe = null;
        $this->verificationEnvoyeLe = null;
        $this->formationsEnseignees = new ArrayCollection();
    }

    private function assertPendingPublicApprovalAction(User $admin): void
    {
        if (!$this->isInscriptionPublique() || !$this->requiresAdminApproval()) {
            throw new InvalidArgumentException('Cette inscription ne requiert pas de décision administrateur.');
        }
        if ($this->statutValidation !== self::STATUT_VALIDATION_ATTENTE) {
            throw new InvalidArgumentException('Seule une inscription en attente peut recevoir une décision.');
        }
        if (!in_array(self::ROLE_ADMIN, $admin->getRoles(), true)) {
            throw new InvalidArgumentException('La décision doit être prise par un administrateur.');
        }
        if ($admin === $this || ($this->id !== null && $admin->getId() !== null && $this->id === $admin->getId())) {
            throw new InvalidArgumentException('Un utilisateur ne peut pas statuer sur sa propre inscription.');
        }
    }

    /** @return list<string> */
    private static function publicRegistrationTypes(): array
    {
        return [
            self::TYPE_INSCRIPTION_APPRENANT,
            self::TYPE_INSCRIPTION_FORMATEUR,
            self::TYPE_INSCRIPTION_ETABLISSEMENT,
        ];
    }

    private static function isKnownRegistrationType(string $type): bool
    {
        return $type === self::TYPE_INSCRIPTION_INTERNE || in_array($type, self::publicRegistrationTypes(), true);
    }

    private static function isKnownValidationStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUT_VALIDATION_APPROUVE,
            self::STATUT_VALIDATION_ATTENTE,
            self::STATUT_VALIDATION_REFUSE,
            self::STATUT_VALIDATION_DISPENSE,
        ], true);
    }

    private function normalizeRequiredName(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value, 'UTF-8') > 100) {
            throw new InvalidArgumentException(sprintf('%s est requis et doit contenir au plus 100 caractères.', $field));
        }

        return $value;
    }

    private function normalizeOptionalText(?string $value, int $maxLength, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException(sprintf('%s doit contenir au plus %d caractères.', $field, $maxLength));
        }

        return $value;
    }
}
