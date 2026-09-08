import ClassRankDefinition from '../../api/definitions/class-rank-definition';
import { CharacterClassSpecialtyProgressDefinition } from '../../api/definitions/class-specialty-definition';

export default interface CurrentClassOverviewProps {
  active_rank: ClassRankDefinition;
  specialties_equipped: CharacterClassSpecialtyProgressDefinition[];
  specialties_loading: boolean;
  on_open_class: () => void;
  on_open_specialty: (id: number) => void;
  on_manage_specialties: () => void;
}
