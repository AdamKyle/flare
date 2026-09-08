import SpecialtyManagementRowDefinition from './specialty-management-row-definition';

export default interface SpecialtyManagementCardProps {
  row: SpecialtyManagementRowDefinition;
  on_click: (gameClassSpecialId: number) => void;
}
