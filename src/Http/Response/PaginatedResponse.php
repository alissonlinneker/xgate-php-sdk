<?php

declare(strict_types=1);

namespace XGateGlobal\SDK\Http\Response;

class PaginatedResponse extends ApiResponse
{
    private int $currentPage;
    private int $totalPages;
    private int $perPage;
    private int $totalItems;
    private bool $hasMore;

    public function __construct(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ) {
        parent::__construct($data, $statusCode, $headers);
        
        $meta = $this->getMeta() ?? [];
        $this->currentPage = $meta['current_page'] ?? 1;
        $this->totalPages = $meta['total_pages'] ?? 1;
        $this->perPage = $meta['per_page'] ?? count($data);
        $this->totalItems = $meta['total_items'] ?? count($data);
        $this->hasMore = $meta['has_more'] ?? ($this->currentPage < $this->totalPages);
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    public function getItems(): array
    {
        return $this->getData()['items'] ?? $this->getData();
    }
}