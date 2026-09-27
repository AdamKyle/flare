import UnitFormDefinition from '../api/definitions/unit-form-definition';

export default interface UnitFormContentProps {
  unit_id: number | null;
  on_saved: (unit: UnitFormDefinition) => void;
  on_cancel: () => void;
}
