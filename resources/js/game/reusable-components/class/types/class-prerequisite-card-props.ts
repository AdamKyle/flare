export default interface ClassPrerequisiteCardProps {
  id: number;
  name: string;
  required_level: number;
  current_level?: number;
  is_met?: boolean;
  on_click?: (id: number) => void;
}
