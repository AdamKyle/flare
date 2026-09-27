import BuildingDetailDefinition from '../../api/definitions/building-detail-definition';

export default interface BuildingDetailProps {
  building: BuildingDetailDefinition;
  on_open_unit?: (unitId: number) => void;
}
