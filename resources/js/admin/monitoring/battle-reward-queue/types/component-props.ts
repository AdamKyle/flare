import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';
import React from 'react';

import {
  CharacterRow,
  RequestFiltersType,
  RewardRequest,
  StaleQueue,
} from '../api/definitions/reward-queue-definition';

export interface RewardQueueCardProps {
  title?: string;
  description?: string;
  children: React.ReactNode;
  className?: string;
}
export interface StaleQueueViewProps {
  staleQueues: StaleQueue[];
  repairing: boolean;
  onBack: () => void;
  onRepair: () => void;
}
export interface StaleQueueAlertProps {
  count: number;
  repairing: boolean;
  onView: () => void;
  onRepair: () => void;
}
export interface RequestFiltersProps {
  filters: RequestFiltersType;
  onChange: (filters: RequestFiltersType) => void;
}
export interface CharacterQueueTableProps {
  characters: PaginatedApiResponseDefinition<CharacterRow[]>;
  onSelect: (character: CharacterRow) => void;
  onPageChange: (page: number) => void;
}
export interface RequestHistoryProps {
  selectedCharacter: CharacterRow | null;
  requests: PaginatedApiResponseDefinition<RewardRequest[]>;
  filters: RequestFiltersType;
  onFiltersChange: (filters: RequestFiltersType) => void;
  onClearCharacter: () => void;
  onPageChange: (page: number) => void;
}
