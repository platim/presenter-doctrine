<?php

declare(strict_types=1);

namespace Platim\Presenter\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Proxy;
use Platim\Presenter\Contracts\Metadata\MetadataRegistryInterface;

class MetadataRegistry implements MetadataRegistryInterface
{
    private array $metadata = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry
    ) {
    }

    public function getMetadataForClass(string $class): ?MetadataProxy
    {
        if (\array_key_exists($class, $this->metadata)) {
            return $this->metadata[$class];
        }
        $em = $this->managerRegistry->getManagerForClass($class);
        $this->metadata[$class] = $em ? new MetadataProxy($em->getClassMetadata($class)) : null;

        return $this->metadata[$class];
    }

    public function getObjectClass(object $object): string
    {
        $class = $object::class;
        if (is_subclass_of($class, Proxy::class)) {
            $class = get_parent_class($class);
        }

        return $class;
    }
}
