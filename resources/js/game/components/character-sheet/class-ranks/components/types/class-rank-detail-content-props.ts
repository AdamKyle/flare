import ClassRankDefinition from '../../api/definitions/class-rank-definition';
import { ClassSpecialtyDefinition } from '../../api/definitions/class-specialty-definition';

export default interface ClassRankDetailContentProps {
  selected_rank: ClassRankDefinition;
  rank_catalog: ClassRankDefinition[];
  class_specialties: ClassSpecialtyDefinition[];
  automation_restricted: boolean;
  switching_class_id: number | null;
  switch_error: string | null;
  on_switch_class: (gameClassId: number) => void;
  on_open_class: (gameClassId: number) => void;
  on_open_specialty: (gameClassSpecialId: number) => void;
  switch_class_action_in_footer?: boolean;
}
