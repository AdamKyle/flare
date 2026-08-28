export interface LocationFormOptionItem {
  value: number;
  label: string;
}

export interface LocationSpecialPinOption {
  value: string;
  label: string;
}

export default interface LocationFormOptionsDefinition {
  game_map: {
    id: number;
    name: string;
  };
  quest_items: LocationFormOptionItem[];
  location_types: LocationFormOptionItem[];
  special_pins: LocationSpecialPinOption[];
  coordinates: {
    x: number[];
    y: number[];
  };
}
