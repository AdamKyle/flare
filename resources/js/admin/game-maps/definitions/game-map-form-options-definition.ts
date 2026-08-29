import { GameMapEventType } from '../enums/game-map-event-type';

export interface GameMapLocationOptionDefinition {
  id: number;
  name: string;
}

export default interface GameMapFormOptionsDefinition {
  event_types: GameMapEventType[];
  locations: GameMapLocationOptionDefinition[];
}
