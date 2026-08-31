import { GameMapIdentityDefinition } from '../quest-item-factual-definition';

export default interface QuestMapRowProps {
  game_map: GameMapIdentityDefinition | null;
  on_open_map?: (id: number) => void;
}
