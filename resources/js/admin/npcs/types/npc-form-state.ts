import { NpcType } from '../enums/npc-type';

export default interface NpcFormState {
  real_name: string;
  type: NpcType | null;
  x_position: number | null;
  y_position: number | null;
}
