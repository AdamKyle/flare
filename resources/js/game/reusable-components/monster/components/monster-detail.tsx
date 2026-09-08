import React, { ReactNode } from 'react';

import MonsterNormalTabPanel from './monster-normal-tab-panel';
import MonsterSpecialLocationEffectsTabPanel from './monster-special-location-effects-tab-panel';
import { MonsterGemEffectContextDefinition } from '../api/definitions/monster-detail-definition';
import MonsterDetailProps from '../types/monster-detail-props';

import PillTabs from 'ui/tabs/pill-tabs';

const TAB_CSS = 'min-w-0 flex-1 px-4 sm:min-w-56 sm:flex-none';

/**
 * Normal values are persisted base values; special-location tabs use cached transformed contexts.
 */
const resolveSingleContextTabLabel = (
  context: MonsterGemEffectContextDefinition
): string => {
  switch (context.type) {
    case 'location':
      return context.location
        ? `Special Location Effects: ${context.location.name}`
        : `Special Location Effects: ${context.label}`;
    case 'location_gem_world':
      return `Location Gem World Effects: ${context.location?.name ?? context.label}`;
    case 'map':
      return `Map Gem Effects: ${context.label}`;
    case 'map_gem_world':
      return `Map Gem World Effects: ${context.label}`;
    default:
      return context.label;
  }
};

const MonsterDetail = ({
  monster,
  navigation,
  initial_context_tab: initialContextTab = false,
}: MonsterDetailProps): ReactNode => {
  const hasGemEffectContexts = monster.gem_effect_contexts.length > 0;
  const hasSingleContext = monster.gem_effect_contexts.length === 1;

  const renderBody = (): ReactNode => {
    if (!hasGemEffectContexts) {
      return (
        <MonsterNormalTabPanel monster={monster} navigation={navigation} />
      );
    }

    const normalTab = {
      label: 'Original Stats',
      component: MonsterNormalTabPanel,
      props: { monster, navigation },
    } as const;

    const specialLocationEffectsTab = {
      label: hasSingleContext
        ? resolveSingleContextTabLabel(monster.gem_effect_contexts[0])
        : 'Special Location Effects',
      component: MonsterSpecialLocationEffectsTabPanel,
      props: { contexts: monster.gem_effect_contexts, navigation },
    } as const;

    const tabs = [normalTab, specialLocationEffectsTab] as const;

    return (
      <PillTabs
        tabs={tabs}
        ariaLabel="Monster detail"
        additional_tab_css={TAB_CSS}
        initialIndex={initialContextTab ? 1 : 0}
      />
    );
  };

  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-glacier-900 dark:text-glacier-100 text-xl font-semibold">
        {monster.identity.name}
      </h1>

      {renderBody()}
    </div>
  );
};

export default MonsterDetail;
