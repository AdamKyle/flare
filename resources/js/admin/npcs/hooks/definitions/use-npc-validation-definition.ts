import NpcFormErrors from '../../types/npc-form-errors';
import NpcFormState from '../../types/npc-form-state';

export default interface UseNpcValidationDefinition {
  errors: NpcFormErrors;
  validate_step: (step_index: number, state: NpcFormState) => boolean;
  validate_all: (state: NpcFormState) => boolean;
  validate_field: (field: keyof NpcFormErrors, state: NpcFormState) => void;
  clear_errors: () => void;
}
