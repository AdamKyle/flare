import React, { ReactNode } from 'react';

import MonsterNormalTabPanel from './monster-normal-tab-panel';
import MonsterSpecialLocationEffectsTabPanel from './monster-special-location-effects-tab-panel';
import MonsterDetailProps from '../types/monster-detail-props';

import PillTabs from 'ui/tabs/pill-tabs';

/**
 * Shared, permission-neutral factual Monster detail presentation. The
 * Normal tab is every value's persisted base value; no combat/map scaling
 * is applied there. When cached Gem effect contexts exist for this Monster,
 * a second Special Location Effects tab shows the cached, already
 * Gem-transformed values for each context. Never checks Admin permission,
 * imports Admin APIs, or mutates data; navigation is entirely driven by the
 * optional `navigation` callbacks.
 */
const MonsterDetail = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => {
  const hasGemEffectContexts = monster.gem_effect_contexts.length > 0;

  const renderBody = (): ReactNode => {
    if (!hasGemEffectContexts) {
      return (
        <MonsterNormalTabPanel monster={monster} navigation={navigation} />
      );
    }

    const normalTab = {
      label: 'Normal',
      component: MonsterNormalTabPanel,
      props: { monster, navigation },
    } as const;

    const specialLocationEffectsTab = {
      label: 'Special Location Effects',
      component: MonsterSpecialLocationEffectsTabPanel,
      props: { contexts: monster.gem_effect_contexts, navigation },
    } as const;

    const tabs = [normalTab, specialLocationEffectsTab] as const;

    return <PillTabs tabs={tabs} ariaLabel="Monster detail" />;
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
