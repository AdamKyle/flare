import ClassRankDefinition from '../../api/definitions/class-rank-definition';
import { ClassSpecialtyDefinition } from '../../api/definitions/class-specialty-definition';

export default interface ClassRankDetailStackProps {
  character_id: number;
  game_class_id: number;
  rank_catalog: ClassRankDefinition[];
  class_specialties: ClassSpecialtyDefinition[];
  automation_restricted: boolean;
  switching_class_id: number | null;
  switch_error: string | null;
  on_close: () => void;
  on_switch_class: (gameClassId: number) => void;
}
