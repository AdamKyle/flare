import BuildingFormDefinition from '../api/definitions/building-form-definition';

export default interface BuildingFormContentProps {
  building_id: number | null;
  on_saved: (building: BuildingFormDefinition) => void;
  on_cancel: () => void;
}
