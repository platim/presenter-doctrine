<?php

declare(strict_types=1);

namespace Platim\Presenter\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Platim\Presenter\Contracts\Metadata\MetadataInterface;

class MetadataProxy implements MetadataInterface
{
    public function __construct(
        private readonly ClassMetadata $classMetadata
    ) {
    }

    public function getFieldNames(): array
    {
        return $this->classMetadata->getFieldNames();
    }

    public function getAssociationNames(): array
    {
        return $this->classMetadata->getAssociationNames();
    }

    public function hasAssociation(string $name): bool
    {
        return $this->classMetadata->hasAssociation($name);
    }

    public function isAssociationMultiple(string $name): bool
    {
        return $this->classMetadata->isCollectionValuedAssociation($name);
    }

    public function getAssociationValueAsArray(mixed $value): array
    {
        if ($value instanceof Collection) {
            return $value->toArray();
        }
        if (\is_array($value)) {
            return $value;
        }

        return [];
    }
}
