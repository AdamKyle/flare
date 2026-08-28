import NpcRequestDefinition from '../api/definitions/npc-request-definition';
import NpcFormState from '../types/npc-form-state';

/**
 * Build the Npc save request payload from validated form state.
 */
export const buildNpcRequest = (state: NpcFormState): NpcRequestDefinition => {
  return {
    real_name: state.real_name.trim(),
    type: state.type ?? 0,
    x_position: state.x_position ?? 0,
    y_position: state.y_position ?? 0,
  };
};
