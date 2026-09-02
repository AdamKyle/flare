import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface QuestBrowseControlsProps {
  game_maps: DropdownItem[];
  selected_game_map_id: number | null;
  on_select_game_map: (id: number) => void;
  importing: boolean;
  on_import_click: () => void;
}
