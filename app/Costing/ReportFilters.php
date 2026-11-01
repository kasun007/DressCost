<?php

namespace App\Costing;

use Carbon\CarbonImmutable;

/**
 * Optional filters for the cost report. Every filter left null means "all".
 */
final readonly class ReportFilters
{
    public function __construct(
        public ?int $workspaceId = null,
        public ?int $projectId = null,
        public ?string $costType = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    /**
     * @return array{workspace_id: int|null, project_id: int|null, cost_type: string|null, from: string|null, to: string|null}
     */
    public function toArray(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'project_id' => $this->projectId,
            'cost_type' => $this->costType,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
        ];
    }
}
