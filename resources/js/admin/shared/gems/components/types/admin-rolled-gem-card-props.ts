import AdminRolledGemDefinition from '../../api/definitions/admin-rolled-gem-definition';

export interface AdminRolledGemCardDisplayField {
  rolled_field: keyof AdminRolledGemDefinition;
  label: string;
}

export interface AdminRolledGemCardDisplayGroup {
  title: string;
  fields: AdminRolledGemCardDisplayField[];
}

export default interface AdminRolledGemCardProps {
  roll: AdminRolledGemDefinition;
  source_label: string;
  source_name: string;
  display_groups: AdminRolledGemCardDisplayGroup[];
  profile_name?: string;
  on_activate?: () => void;
  activating?: boolean;
}
