export interface GameMapEventTypeOptionDefinition {
  value: number;
  label: string;
}

export interface GameMapLocationOptionDefinition {
  id: number;
  name: string;
}

export default interface GameMapFormOptionsDefinition {
  event_types: GameMapEventTypeOptionDefinition[];
  locations: GameMapLocationOptionDefinition[];
}
