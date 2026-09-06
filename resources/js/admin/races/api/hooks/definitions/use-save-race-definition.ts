import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RaceDefinition from '../../definitions/race-definition';

export default interface UseSaveRaceDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    race_id: number | null,
    form_data: FormData
  ) => Promise<RaceDefinition | null>;
  clear_field_error: (field: string) => void;
}
