import { GameMapRelatedDataKey } from '../../types/game-map-related-data-entry';

export default interface GameMapRelatedDataNavigationProps {
  game_map_id: number;
  on_open_related?: (key: GameMapRelatedDataKey) => void;
}
