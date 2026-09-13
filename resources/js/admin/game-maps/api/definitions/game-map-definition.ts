import { GameMapType } from '../../enums/game-map-type';

export default interface GameMapDefinition {
  id: number;
  name: string;
  map_type: GameMapType;
  map_type_label: string;
  plane: string;
  source: string | null;
}
