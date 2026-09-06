export default interface ClassMasteryListDefinition {
  id: number;
  name: string;
  game_class: {
    id: number;
    name: string;
  };
  type: 'attack' | 'passive';
  requires_class_rank_level: number;
}
