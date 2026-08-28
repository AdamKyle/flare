import { GameMapKingdomOwnerType } from '../enums/game-map-kingdom-owner-type';

export default interface GameMapKingdomMarkerDefinition {
  id: number;
  name: string;
  npc_owned: boolean;
  owner_type: GameMapKingdomOwnerType;
  x_position: number;
  y_position: number;
}
