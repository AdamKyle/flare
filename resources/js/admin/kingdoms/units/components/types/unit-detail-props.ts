import UnitDetailDefinition from '../../api/definitions/unit-detail-definition';

export default interface UnitDetailProps {
  unit: UnitDetailDefinition;
  on_open_building?: (buildingId: number) => void;
}
