import AdminRolledGemDefinition from '../../../shared/gems/api/definitions/admin-rolled-gem-definition';

export interface MapGemBulkRollResultItemDefinition {
  profile_id: number;
  profile_name: string;
  source_name: string;
  rolled_gem: AdminRolledGemDefinition;
}

export default interface MapGemBulkRollResultDefinition {
  rolled_count: number;
  rolled: MapGemBulkRollResultItemDefinition[];
}
