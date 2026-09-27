export default interface BuildingCardProps {
  building_id: number;
  name: string;
  required_level?: number | null;
  description?: string | null;
  on_open_building?: (buildingId: number) => void;
}
