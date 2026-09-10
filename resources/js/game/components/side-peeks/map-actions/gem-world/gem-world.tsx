import React, { ReactNode, useState } from 'react';

import GemWorldProps from './types/gem-world-props';
import GemWorldSourceDefinition from '../../../../reusable-components/gems/api/definitions/gem-world-source-definition';
import AreaGemContext from '../../../../reusable-components/gems/components/area-gem-context';
import AreaGemSourceCard from '../../../../reusable-components/gems/components/area-gem-source-card';
import RolledGemSourceDetail from '../../../../reusable-components/gems/components/rolled-gem-source-detail';
import {
  LOCATION_GEM_PLAYER_DISPLAY_GROUPS,
  MAP_GEM_PLAYER_DISPLAY_GROUPS,
} from '../../../../reusable-components/gems/definitions/player-rolled-gem-display-groups';
import { useEnterGemWorld } from '../../../map-section/api/hooks/use-enter-gem-world';
import { useCloseSidePeekEmitter } from '../../base/hooks/use-close-side-peek-emitter';
import { useEmitMapRefresh } from '../traverse/hooks/use-emit-map-refresh';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const displayGroupsForSource = (source: GemWorldSourceDefinition) =>
  source.type === 'map_gem'
    ? MAP_GEM_PLAYER_DISPLAY_GROUPS
    : LOCATION_GEM_PLAYER_DISPLAY_GROUPS;

const GemWorld = ({
  character_id: characterId,
  context,
  can_enter: canEnter,
}: GemWorldProps): ReactNode => {
  const [selectedSource, setSelectedSource] =
    useState<GemWorldSourceDefinition | null>(null);

  const {
    loading: entering,
    error: enterError,
    action: enterGemWorld,
  } = useEnterGemWorld(characterId);
  const { emitShouldRefreshMap } = useEmitMapRefresh();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleEnter = async (): Promise<void> => {
    const entered = await enterGemWorld();

    if (!entered) {
      return;
    }

    emitShouldRefreshMap(true);
    closeSidePeek();
  };

  const resolveFooterOptions = (): SidePeekOptionDefinition[] => {
    if (!canEnter) {
      return [];
    }

    return [
      {
        id: 'enter-gem-world',
        label: 'Enter Gem World',
        loading_label: 'Entering Gem World…',
        variant: ButtonVariant.PRIMARY,
        loading: entering,
        on_click: () => void handleEnter(),
      },
    ];
  };

  useSidePeekOptions(resolveFooterOptions());

  const handleSelectSource = (source: GemWorldSourceDefinition): void => {
    setSelectedSource(source);
  };

  const handleCloseSourceDetail = (): void => {
    setSelectedSource(null);
  };

  const renderRules = (): ReactNode => {
    if (context.rules.length === 0) {
      return null;
    }

    return (
      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Rules Applied
        </h3>
        <ul className="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
          {context.rules.map((rule) => (
            <li key={rule}>{rule}</li>
          ))}
        </ul>
      </div>
    );
  };

  const renderSourceDetail = (): ReactNode => {
    if (!selectedSource) {
      return null;
    }

    const isMapGem = selectedSource.type === 'map_gem';

    return (
      <StackedCard
        on_close={handleCloseSourceDetail}
        content_mode={StackedCardContentMode.FULL_BLEED}
        aria_label={`${isMapGem ? 'Map Gem' : 'Location Gem'}: ${selectedSource.profile_name}`}
      >
        <div className="h-full overflow-y-auto px-4 py-4 sm:px-5">
          <RolledGemSourceDetail
            source={selectedSource}
            display_groups={displayGroupsForSource(selectedSource)}
          />
        </div>
      </StackedCard>
    );
  };

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      {enterError && <Alert variant={AlertVariant.DANGER}>{enterError}</Alert>}
      {renderRules()}
      <AreaGemContext context={context} />
      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Gem Sources
        </h3>
        <div className="flex flex-col gap-2">
          {context.sources.map((source) => (
            <AreaGemSourceCard
              key={`${source.type}-${source.profile_id}`}
              source={source}
              on_click={handleSelectSource}
            />
          ))}
        </div>
      </div>
      {renderSourceDetail()}
    </div>
  );
};

export default GemWorld;
