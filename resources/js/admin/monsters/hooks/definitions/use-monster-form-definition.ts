import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MonsterFormDefinition from '../../api/definitions/monster-form-definition';
import { useMonsterFormOptions } from '../../api/hooks/use-monster-form-options';
import MonsterFormErrorsDefinition from '../../definitions/monster-form-errors-definition';
import MonsterFormStateDefinition from '../../definitions/monster-form-state-definition';

type MonsterFormStateFieldKey = Extract<
  keyof MonsterFormStateDefinition,
  string
>;

export default interface UseMonsterFormDefinition {
  form_state: MonsterFormStateDefinition;
  update_field: <K extends MonsterFormStateFieldKey>(
    field: K,
    value: MonsterFormStateDefinition[K]
  ) => void;
  form_options: ReturnType<typeof useMonsterFormOptions>['form_options'];
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: MonsterFormErrorsDefinition;
  submit: () => Promise<MonsterFormDefinition | null>;
  validate_step: (stepIndex: number) => boolean;
}
