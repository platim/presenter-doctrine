<?php

declare(strict_types=1);

namespace Platim\Presenter\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

class MetadataRegistry
{
    private array $metadata = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry
    ) {
    }

    public function getMetadataForClass(string $class): ?ClassMetadata
    {
        if (\array_key_exists($class, $this->metadata)) {
            return $this->metadata[$class];
        }
        $em = $this->managerRegistry->getManagerForClass($class);
        if ($em) {
            return $this->metadata[$class] = $em->getClassMetadata($class);
        }

        return $this->metadata[$class] = null;
    }
}
