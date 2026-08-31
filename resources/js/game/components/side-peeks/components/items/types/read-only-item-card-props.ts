export default interface ReadOnlyItemCardProps {
  item_id: number;
  name: string;
  description: string;
  effect: string | null;
  usable: boolean;
  on_click: (item_id: number) => void;
}
