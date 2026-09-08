import SpecialtyManagementRowDefinition from './specialty-management-row-definition';
import ClassRankDefinition from '../../../api/definitions/class-rank-definition';

export default interface SpecialtyLibraryProps {
  rows: SpecialtyManagementRowDefinition[];
  class_ranks: ClassRankDefinition[];
  current_game_class_id: number;
  on_open_specialty: (gameClassSpecialId: number) => void;
}
