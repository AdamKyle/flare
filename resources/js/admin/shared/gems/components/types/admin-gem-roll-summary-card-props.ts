import AdminRolledGemDefinition from '../../api/definitions/admin-rolled-gem-definition';

export default interface AdminGemRollSummaryCardProps {
  roll: AdminRolledGemDefinition;
  on_click: () => void;
}
