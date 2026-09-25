import React, { ReactNode, useState } from 'react';

import GemWorldProps from './types/gem-world-props';
import AreaGemEffectSummary from '../../../../reusable-components/gems/components/area-gem-effect-summary';
import GemContextEffectsDetail from '../../../../reusable-components/gems/components/gem-context-effects-detail';
import GemContextProfileDetail from '../../../../reusable-components/gems/components/gem-context-profile-detail';
import { useEnterGemWorld } from '../../../map-section/api/hooks/use-enter-gem-world';
import { useCloseSidePeekEmitter } from '../../base/hooks/use-close-side-peek-emitter';
import { useEmitMapRefresh } from '../traverse/hooks/use-emit-map-refresh';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

type ActiveDetail = 'effects' | 'profile' | null;

const GemWorld = ({
  character_id: characterId,
  context,
  can_enter: canEnter,
}: GemWorldProps): ReactNode => {
  const [activeDetail, setActiveDetail] = useState<ActiveDetail>(null);

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
    if (!canEnter || activeDetail !== null) {
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

  const isLocationContext =
    context.type === 'location' || context.type === 'location_gem_world';
  const contextTypeLabel = isLocationContext
    ? 'Location Gem World'
    : 'Map Gem World';

  const primarySource =
    context.sources.find((source) =>
      isLocationContext
        ? source.type === 'location_gem'
        : source.type === 'map_gem'
    ) ??
    context.sources[0] ??
    null;

  const destinationName = primarySource?.gem_world_name ?? context.label;

  const renderEffectsDetail = (): ReactNode => {
    if (activeDetail !== 'effects') {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setActiveDetail(null)}
        content_mode={StackedCardContentMode.FULL_BLEED}
        aria_label="Gem Effects"
      >
        <GemContextEffectsDetail context={context} />
      </StackedCard>
    );
  };

  const renderProfileDetail = (): ReactNode => {
    if (activeDetail !== 'profile') {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setActiveDetail(null)}
        content_mode={StackedCardContentMode.FULL_BLEED}
        aria_label="Gem Profile"
      >
        <GemContextProfileDetail
          context={context}
          description="This rolled Gem defines the base modifiers and rules applied to this Gem World."
        />
      </StackedCard>
    );
  };

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      {enterError && <Alert variant={AlertVariant.DANGER}>{enterError}</Alert>}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {destinationName}
        </h2>
        <p className="text-glacier-700 dark:text-glacier-300 text-xs font-semibold tracking-wide uppercase">
          {contextTypeLabel}
        </p>
      </div>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Entering applies this Gem World&apos;s modifiers: Monsters and rewards
        may become stronger or better, and your progression grows while you
        fight inside it.
      </p>
      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Effect Summary
        </h3>
        <AreaGemEffectSummary context={context} />
      </div>
      <div className="flex flex-col gap-2 sm:flex-row">
        <Button
          label="View Gem Effects"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full sm:flex-1"
          on_click={() => setActiveDetail('effects')}
        />
        <Button
          label="View Gem Profile"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full sm:flex-1"
          on_click={() => setActiveDetail('profile')}
        />
      </div>
      {renderEffectsDetail()}
      {renderProfileDetail()}
    </div>
  );
};

export default GemWorld;
