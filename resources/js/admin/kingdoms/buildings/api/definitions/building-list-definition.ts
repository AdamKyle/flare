export default interface BuildingListDefinition {
  id: number;
  name: string;
  max_level: number;
  trains_units: boolean;
  is_resource_building: boolean;
  is_locked: boolean;
  is_special: boolean | null;
}
