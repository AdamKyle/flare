export default interface UnitListDefinition {
  id: number;
  name: string;
  attack: number;
  defence: number;
  time_to_recruit: number;
  is_special: boolean | null;
}
