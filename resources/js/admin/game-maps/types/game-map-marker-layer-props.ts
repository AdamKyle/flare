import GameMapKingdomMarkerDefinition from '../api/definitions/game-map-kingdom-marker-definition';
import GameMapLocationMarkerDefinition from '../api/definitions/game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from '../api/definitions/game-map-npc-marker-definition';

export default interface GameMapMarkerLayerProps {
  locations: GameMapLocationMarkerDefinition[];
  npcs: GameMapNpcMarkerDefinition[];
  kingdoms: GameMapKingdomMarkerDefinition[];
  on_location_selected: (location_id: number) => void;
  on_npc_selected: (npc_id: number) => void;
  on_kingdom_selected: (kingdom: GameMapKingdomMarkerDefinition) => void;
}
