<?php

declare(strict_types=1);

namespace Platim\Presenter\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Platim\Presenter\Contracts\DataProvider\QueryBuilder\QueryBuilderInterface;

class QueryBuilderProxy implements QueryBuilderInterface
{
    private QueryBuilder $queryBuilder;
    private int $paramCounter = 0;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    public function setLimit(int $limit): void
    {
        $this->queryBuilder->setMaxResults($limit);
    }

    public function setOffset(int $offset): void
    {
        $this->queryBuilder->setFirstResult($offset);
    }

    public function queryCount(): int
    {
        $qbCount = (clone $this->queryBuilder)
            ->resetDQLPart('orderBy')
            ->setFirstResult(null)
            ->setMaxResults(null)
        ;
        $alias = $qbCount->getRootAliases()[0];

        return (int) $qbCount->select("count(distinct $alias)")->getQuery()->getSingleScalarResult();
    }

    public function fetchAll(): array
    {
        return $this->queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function getFilterMap(): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator();

        foreach ($iterator->aliasIterate($this->queryBuilder) as $aliasItem) {
            /** @var ClassMetadata $metadata */
            foreach ($aliasItem as $alias => $metadata) {
                foreach ($iterator->fieldsIterate($alias, $aliasItem) as $filterName => $fieldName) {
                    [, $realName] = explode('.', $fieldName);
                    $filterName = str_replace('.', '_', $filterName);
                    if ($metadata->isSingleValuedAssociation($realName)) {
                        $result[$filterName] = [$fieldName, null];
                    } else {
                        $result[$filterName] = [$fieldName, $metadata->getTypeOfField($realName)];
                    }
                }
            }
        }

        return $result;
    }

    public function addFilter(string $fieldName, ?string $fieldType, $filterValue): void
    {
        ++$this->paramCounter;
        $p = 'P_' . $this->paramCounter;
        if (\is_string($filterValue)) {
            $filterValueArr = array_filter(explode(',', $filterValue), 'trim');
            $filterValue = \count($filterValueArr) > 1 ? $filterValueArr : $filterValue;
        }
        switch ($fieldType) {
            case Types::BOOLEAN:
            case Types::DATETIME_MUTABLE:
            case Types::DATETIMETZ_MUTABLE:
            case Types::DATETIME_IMMUTABLE:
            case Types::DATETIMETZ_IMMUTABLE:
            case Types::DATE_MUTABLE:
            case Types::DATE_IMMUTABLE:
            case Types::TIME_MUTABLE:
            case Types::TIME_IMMUTABLE:
            case Types::DECIMAL:
            case Types::FLOAT:
            case Types::INTEGER:
            case Types::BIGINT:
            case Types::SMALLINT:
                if (\is_array($filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } elseif ($filterValue && is_numeric($filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            case Types::STRING:
            case Types::TEXT:
            case null:
                if (\is_array($filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName IN (:$p)")->setParameter($p, $filterValue);
                } elseif (\is_string($filterValue) && preg_match('/^%(.+)%$/', $filterValue)) {
                    $this->queryBuilder->andWhere("$fieldName LIKE :$p")->setParameter($p, $filterValue);
                } elseif ($filterValue) {
                    $this->queryBuilder->andWhere("$fieldName = :$p")->setParameter($p, $filterValue);
                }
                break;
            default:
                break;
        }
    }

    public function getSortMap(): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator();

        foreach ($iterator->aliasIterate($this->queryBuilder) as $alias => $aliasItem) {
            foreach ($iterator->fieldsIterate($alias, $aliasItem) as $sortName => $fieldName) {
                $result[$sortName] = $fieldName;
            }
        }

        return $result;
    }

    public function addOrderBy(string $sort, string $order): void
    {
        $this->queryBuilder->addOrderBy($sort, $order);
    }

    public function resetOrder(): void
    {
        $this->queryBuilder->resetDQLPart('orderBy');
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
