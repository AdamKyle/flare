import { CharacterClassSpecialtyProgressDefinition } from '../../api/definitions/class-specialty-definition';

export default interface EquippedSpecialtiesSummaryProps {
  specialties_equipped: CharacterClassSpecialtyProgressDefinition[];
  specialties_loading: boolean;
  on_open_specialty: (id: number) => void;
  on_manage_specialties: () => void;
}
