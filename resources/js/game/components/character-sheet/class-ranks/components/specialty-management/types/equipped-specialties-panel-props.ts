import SpecialtyManagementRowDefinition from './specialty-management-row-definition';

export default interface EquippedSpecialtiesPanelProps {
  equipped_rows: SpecialtyManagementRowDefinition[];
  on_open_specialty: (gameClassSpecialId: number) => void;
}
