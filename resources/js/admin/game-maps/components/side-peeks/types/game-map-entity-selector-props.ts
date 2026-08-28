export interface GameMapSelectableEntityDefinition {
  id: number;
  label: string;
  x: number;
  y: number;
}

export default interface GameMapEntitySelectorProps {
  entities: GameMapSelectableEntityDefinition[];
  search_label: string;
  empty_message: string;
  on_select: (entity_id: number) => void;
}
