import NpcDefinition from '../api/definitions/npc-definition';
import NpcFormState from '../types/npc-form-state';

export const createNpcFormState = (
  npc: NpcDefinition | null,
  initialX: number | null,
  initialY: number | null
): NpcFormState => {
  if (npc) {
    return {
      real_name: npc.real_name,
      type: npc.type,
      x_position: npc.x_position,
      y_position: npc.y_position,
    };
  }

  return {
    real_name: '',
    type: null,
    x_position: initialX,
    y_position: initialY,
  };
};
