<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_activities",
 *      indexes={
 *          @ORM\Index(columns={"visible","project_id"}),
 *          @ORM\Index(columns={"visible","project_id","name"}),
 *          @ORM\Index(columns={"visible","name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "ProjectName",
 *      exp="object.getProject() === null ? null : object.getProject().getName()",
 *      options={
 *          @Serializer\SerializedName("parentTitle"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"Activity"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "ProjectAsId",
 *      exp="object.getProject() === null ? null : object.getProject().getId()",
 *      options={
 *          @Serializer\SerializedName("project"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Default"})
 *      }
 * )
 */
class Activity implements EntityWithMetaFields
{
    /**
     * Internal ID
     *
     * @var int|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;
    /**
     * Name of this activity
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=150, allowEmptyString=false)
     */
    private $name;
    /**
     * Description of this activity
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity_Entity"})
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;
    /**
     * Whether this activity is visible and can be used for timesheets
     *
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false, options={"default": true})
     * @Assert\NotNull()
     */
    private $visible = true;

    // keep the traits here, for placing the column at the "correct" position
    use ColorTrait;

    /**
     * The total monetary budget, will be zero if unconfigured.
     *
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity_Entity"})
     *
     * @ORM\Column(name="budget", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $budget = 0.00;
    /**
     * The time budget in seconds, will be be zero if unconfigured.
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity_Entity"})
     *
     * @ORM\Column(name="time_budget", type="integer", nullable=false)
     * @Assert\NotNull()
     */
    private $timeBudget = 0;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this activity
     *
     * @var ActivityMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity"})
     * @Serializer\Type(name="array<App\Entity\ActivityMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ActivityMeta", mappedBy="activity", cascade={"persist"})
     */
    private $meta;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): Activity
    {
        $this->project = $project;

        return $this;
    }

    public function setName(string $name): Activity
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setComment(?string $comment): Activity
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): Activity
    {
        $this->visible = $visible;

        return $this;
    }

    public function isGlobal(): bool
    {
        return $this->project === null;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setBudget(float $budget): Activity
    {
        $this->budget = $budget;

        return $this;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function setTimeBudget(int $seconds): Activity
    {
        $this->timeBudget = $seconds;

        return $this;
    }

    public function getTimeBudget(): int
    {
        return $this->timeBudget;
    }

    /**
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getVisibleMetaFields(): array
    {
        $all = [];
        foreach ($this->meta as $meta) {
            if ($meta->isVisible()) {
                $all[] = $meta;
            }
        }

        return $all;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if (strtolower($field->getName()) === strtolower($name)) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        if (null === ($current = $this->getMetaField($meta->getName()))) {
            $meta->setEntity($this);
            $this->meta->add($meta);

            return $this;
        }

        $current->merge($meta);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->meta = new ArrayCollection();
        }
    }
}
