import { useCallback, useEffect, useState } from 'react';

import UseMapFactionScreenNavigationDefinition from './definitions/use-map-faction-screen-navigation-definition';
import UseMapFactionScreenNavigationParams from './definitions/use-map-faction-screen-navigation-params';
import FactionDefinition from '../../../../../factions/api/definitions/faction-definition';
import { MapCardScreen } from '../enums/map-card-screen';

export const useMapFactionScreenNavigation = ({
  pledged_faction_id: pledgedFactionId,
}: UseMapFactionScreenNavigationParams): UseMapFactionScreenNavigationDefinition => {
  const [activeScreen, setActiveScreen] = useState<MapCardScreen>(
    MapCardScreen.MAP
  );
  const [selectedFaction, setSelectedFaction] =
    useState<FactionDefinition | null>(null);
  const [selectedNpcId, setSelectedNpcId] = useState<number | null>(null);

  const isViewingFactionLoyalty =
    activeScreen === MapCardScreen.FACTION_LOYALTY ||
    activeScreen === MapCardScreen.FACTION_LOYALTY_NPC;
  const isSelectedFactionPledged =
    selectedFaction !== null && selectedFaction.id === pledgedFactionId;

  useEffect(() => {
    if (!isViewingFactionLoyalty || isSelectedFactionPledged) {
      return;
    }

    setSelectedNpcId(null);
    setActiveScreen(MapCardScreen.FACTION_DETAIL);
  }, [isViewingFactionLoyalty, isSelectedFactionPledged]);

  const openFactions = useCallback(() => {
    setActiveScreen(MapCardScreen.FACTIONS);
  }, []);

  const openFaction = useCallback((faction: FactionDefinition) => {
    setSelectedFaction(faction);
    setActiveScreen(MapCardScreen.FACTION_DETAIL);
  }, []);

  const openFactionLoyalty = useCallback(() => {
    setActiveScreen(MapCardScreen.FACTION_LOYALTY);
  }, []);

  const openNpc = useCallback((factionLoyaltyNpcId: number) => {
    setSelectedNpcId(factionLoyaltyNpcId);
    setActiveScreen(MapCardScreen.FACTION_LOYALTY_NPC);
  }, []);

  const goBack = useCallback(() => {
    if (activeScreen === MapCardScreen.FACTION_LOYALTY_NPC) {
      setSelectedNpcId(null);
      setActiveScreen(MapCardScreen.FACTION_LOYALTY);

      return;
    }

    if (activeScreen === MapCardScreen.FACTION_LOYALTY) {
      setActiveScreen(MapCardScreen.FACTION_DETAIL);

      return;
    }

    if (activeScreen === MapCardScreen.FACTION_DETAIL) {
      setSelectedFaction(null);
      setActiveScreen(MapCardScreen.FACTIONS);

      return;
    }

    setActiveScreen(MapCardScreen.MAP);
  }, [activeScreen]);

  return {
    active_screen: activeScreen,
    selected_faction: selectedFaction,
    selected_npc_id: selectedNpcId,
    open_factions: openFactions,
    open_faction: openFaction,
    update_selected_faction: setSelectedFaction,
    open_faction_loyalty: openFactionLoyalty,
    open_npc: openNpc,
    go_back: goBack,
  };
};
