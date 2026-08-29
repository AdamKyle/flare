import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export interface DeleteItemResultDefinition {
  message: string;
  blockers?: string[];
}

export default interface UseDeleteItemDefinition {
  deleting: boolean;
  error: AxiosErrorDefinition | null;
  blockers: string[];
  delete_item: (item_id: number) => Promise<boolean>;
}
