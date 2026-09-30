import React from 'react';

import { useGuideTarget } from '../../../../guide-quests/walkthrough/hooks/use-guide-target';
import { GuideTargets } from '../../../../guide-quests/walkthrough/registry/guide-targets';
import AffixCoreAttributesSection from '../partials/affix-view/affix-core-attributes-section';
import AffixDamageSection from '../partials/affix-view/affix-damage-section';
import AffixMiscModifiersSection from '../partials/affix-view/affix-misc-modifiers-section';
import AffixSkillModifiersSection from '../partials/affix-view/affix-skill-modifier-section';
import AffixStatsReductionSection from '../partials/affix-view/affix-stat-reduction-section';
import AffixStatsSection from '../partials/affix-view/affix-stats-section';
import AttachedAffixDetailsProps from '../types/attached-affix-details-props';

import Separator from 'ui/separator/separator';

const AttachedAffixDetails = ({ affix }: AttachedAffixDetailsProps) => {
  const itemAffixDetailsGuideTarget = useGuideTarget(
    GuideTargets.ITEM_AFFIX_DETAILS
  );

  const renderAffixHeader = () => {
    return (
      <div>
        <h2 className="my-2 text-lg text-gray-800 dark:text-gray-300">
          {affix.name}
        </h2>
        <Separator />
        <p className="my-4 text-gray-800 dark:text-gray-300">
          {affix.description}
        </p>
        <Separator />
      </div>
    );
  };

  return (
    <div {...itemAffixDetailsGuideTarget}>
      <div className="flex flex-col gap-4 px-4 pb-4">
        {renderAffixHeader()}

        <div className="space-y-4">
          <AffixStatsSection affix={affix} />
          <AffixStatsReductionSection affix={affix} />
          <AffixCoreAttributesSection affix={affix} />
          <AffixDamageSection affix={affix} />
          <AffixSkillModifiersSection affix={affix} />
          <AffixMiscModifiersSection affix={affix} />
        </div>
      </div>
    </div>
  );
};

export default AttachedAffixDetails;
