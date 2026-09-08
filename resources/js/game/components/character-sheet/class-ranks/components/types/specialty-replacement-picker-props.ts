import { CharacterClassSpecialtyProgressDefinition } from '../../api/definitions/class-specialty-definition';

export default interface SpecialtyReplacementPickerProps {
  equipped_specialties: CharacterClassSpecialtyProgressDefinition[];
  selected_id: number | null;
  on_select: (classSpecialEquippedId: number) => void;
}
