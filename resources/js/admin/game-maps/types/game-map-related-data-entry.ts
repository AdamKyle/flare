export type GameMapRelatedDataKey =
  'locations' | 'npcs' | 'monsters' | 'quests' | 'quest-items';

export default interface GameMapRelatedDataEntry {
  key: GameMapRelatedDataKey;
  label: string;
  mobile_label: string;
  icon_class: string;
  on_click: () => void;
}
