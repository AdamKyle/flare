import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import React from 'react';

import {
  ActiveExplorer,
  ExplorationFilters,
  ExplorationLogRow,
} from '../api/definitions/exploration-monitoring-definition';

export interface MonitoringCardProps {
  title?: string;
  description?: string;
  children: React.ReactNode;
  onClick?: () => void;
  ariaLabel?: string;
}

export interface ExplorationLogsTableProps {
  logs: PaginatedApiResponseDefinition<ExplorationLogRow[]>;
  filters: ExplorationFilters;
  onFiltersChange: (filters: ExplorationFilters) => void;
  onPageChange: (page: number) => void;
}

export interface ActiveExplorersTableProps {
  explorers: ActiveExplorer[];
}
