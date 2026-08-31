import NpcFormErrors from './npc-form-errors';
import NpcFormState from './npc-form-state';
import NpcFormOptionsDefinition from '../api/definitions/npc-form-options-definition';

export default interface NpcBasicFieldsProps {
  game_map_name: string;
  state: NpcFormState;
  errors: NpcFormErrors;
  form_options: NpcFormOptionsDefinition;
  on_change: <K extends keyof NpcFormState>(
    field: K,
    value: NpcFormState[K]
  ) => void;
}
