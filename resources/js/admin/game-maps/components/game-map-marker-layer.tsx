import React, { ReactNode } from 'react';

import GameMapMarker from './game-map-marker';
import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';
import GameMapMarkerLayerProps from '../types/game-map-marker-layer-props';

const GameMapMarkerLayer = ({
  locations,
  npcs,
  kingdoms,
  on_location_selected: onLocationSelected,
  on_npc_selected: onNpcSelected,
  on_kingdom_selected: onKingdomSelected,
}: GameMapMarkerLayerProps): ReactNode => (
  <>
    {locations.map((location) => (
      <GameMapMarker
        key={`location-${location.id}`}
        variant={GameMapMarkerVariant.Location}
        left={location.x}
        top={location.y}
        accessible_name={`Location: ${location.name}`}
        on_activate={() => onLocationSelected(location.id)}
      />
    ))}
    {npcs.map((npc) => (
      <GameMapMarker
        key={`npc-${npc.id}`}
        variant={GameMapMarkerVariant.Npc}
        left={npc.x_position}
        top={npc.y_position}
        accessible_name={`Npc: ${npc.real_name}`}
        on_activate={() => onNpcSelected(npc.id)}
      />
    ))}
    {kingdoms.map((kingdom) => (
      <GameMapMarker
        key={`kingdom-${kingdom.id}`}
        variant={GameMapMarkerVariant.Kingdom}
        left={kingdom.x_position}
        top={kingdom.y_position}
        accessible_name={`Kingdom: ${kingdom.name}`}
        on_activate={() => onKingdomSelected(kingdom)}
      />
    ))}
  </>
);

export default GameMapMarkerLayer;
