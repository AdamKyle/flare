export default interface LocationCardProps {
  location_id: number;
  name: string;
  type_label?: string | null;
  x?: number | null;
  y?: number | null;
  game_map_name?: string | null;
  on_open_location?: (location_id: number) => void;
}
