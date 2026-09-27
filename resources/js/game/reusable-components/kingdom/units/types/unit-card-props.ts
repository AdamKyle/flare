export default interface UnitCardProps {
  unit_id: number;
  name: string;
  required_level?: number | null;
  attack?: number | null;
  defence?: number | null;
  on_open_unit?: (unitId: number) => void;
}
