import RolledGemDefinition from '../api/definitions/rolled-gem-definition';

export interface RolledGemDisplayField {
  rolled_field: keyof RolledGemDefinition;
  label: string;
}

export interface RolledGemDisplayGroup {
  title: string;
  fields: RolledGemDisplayField[];
}
