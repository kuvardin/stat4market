<?php

declare(strict_types=1);

namespace App\Api\v1;

use Kuvardin\TinyOrm\Column;
use Kuvardin\TinyOrm\Enums\SortDirection;
use Kuvardin\TinyOrm\Sorting\Sorting;
use Kuvardin\TinyOrm\Sorting\SortingSettings;
use RuntimeException;

class ApiSelectionData
{
    protected ?int $page = null;

    public function __construct(
        protected ?int $limit = null,
        public ?int $limit_max = null,
        protected ?int $offset = null,
        public ?array $sort_by_variants = null,
        protected ?string $sort_by = null,
        public ?SortDirection $sort_direction = null,
        public ?int $total_amount = null,
    )
    {
        $this->setLimit($this->limit);
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return $this
     */
    public function setLimit(?int $limit, bool $no_limit = false): self
    {
        if ($limit !== null && $limit <= 0) {
            throw new RuntimeException("Incorrect limit: $limit");
        }

        if ($no_limit) {
            $this->limit = null;
        } elseif ($limit !== null && $this->limit_max !== null && $limit > $this->limit_max) {
            $this->limit = $this->limit_max;
        } else {
            $this->limit = $limit;
        }

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        if ($offset !== null && $offset < 0) {
            throw new RuntimeException("Incorrect offset: $offset");
        }

        $this->offset = $offset;
        return $this;
    }

    public function getSortBy(string $prefix = null): ?string
    {
        if ($this->sort_by !== null) {
            return ($prefix ?? '') . (array_key_exists($this->sort_by, $this->sort_by_variants)
                    ? $this->sort_by_variants[$this->sort_by]
                    : $this->sort_by);
        }

        return null;
    }

    public function getSortByFull(string $prefix = null): ?array
    {
        $result = $this->getSortBy($prefix);
        if ($result !== null) {
            return [
                $result => $this->sort_direction?->value,
            ];
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setSortBy(?string $sort_by): self
    {
        if (
            $sort_by !== null
            && $this->sort_by_variants !== null
            && !array_key_exists($sort_by, $this->sort_by_variants)
            && !in_array($sort_by, $this->sort_by_variants, true)
        ) {
            throw new RuntimeException("Unknown sort by field: $sort_by");
        }

        $this->sort_by = $sort_by;
        return $this;
    }

    public function calcPage(): int
    {
        if ($this->limit === null) {
            throw new RuntimeException('Limit must be not null');
        }

        if ($this->total_amount === null) {
            throw new RuntimeException('Total amount must not be null');
        }

        if (empty($this->offset)) {
            return 1;
        }

        return (int)($this->offset / $this->limit) + 1;
    }

    public function getPagesNumber(): int
    {
        if ($this->limit === null) {
            throw new RuntimeException('Limit must be not null');
        }

        if ($this->total_amount === null) {
            throw new RuntimeException('Total amount must not be null');
        }

        $result = (int)($this->total_amount / $this->limit);
        if ($this->total_amount % $this->limit) {
            $result++;
        }

        return $result;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(int $page): int
    {
        if ($this->limit === null) {
            throw new RuntimeException('Limit must be not null');
        }

        if ($this->total_amount === null) {
            throw new RuntimeException('Total amount must not be null');
        }

        if ($this->total_amount === 0) {
            $this->offset = 0;
        } else {
            $offset = $this->limit * ($page - 1);
            if ($offset >= $this->total_amount) {
                $page = (int)($this->total_amount / $this->limit) + 1;
                $this->offset = $this->limit * ($page - 1);
            } else {
                $this->offset = $offset;
            }
        }

        $this->page = $page;
        return $page;
    }

    public function getSortingSettings(): SortingSettings
    {
        return new SortingSettings([
            new Sorting(
                expression: new Column($this->getSortBy()),
                direction: $this->sort_direction,
            ),
        ]);
    }
}
