import React from 'react';

import AffixCoreAttributesSection from '../partials/affix-view/affix-core-attributes-section';
import AffixDamageSection from '../partials/affix-view/affix-damage-section';
import AffixMiscModifiersSection from '../partials/affix-view/affix-misc-modifiers-section';
import AffixSkillModifiersSection from '../partials/affix-view/affix-skill-modifier-section';
import AffixStatsSection from '../partials/affix-view/affix-stats-section';
import AttachedAffixDetailsProps from '../types/attached-affix-details-props';

import Separator from 'ui/separator/separator';

const AttachedAffixDetails = ({ affix }: AttachedAffixDetailsProps) => {
  const renderAffixHeader = () => {
    return (
      <div>
        <h2 className="text-lg my-2 text-gray-800 dark:text-gray-300">
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
    <>
      <div className="px-4 flex flex-col gap-4 pb-4">
        {renderAffixHeader()}

        <div className="space-y-4">
          <AffixStatsSection affix={affix} />
          <AffixCoreAttributesSection affix={affix} />
          <AffixDamageSection affix={affix} />
          <AffixSkillModifiersSection affix={affix} />
          <AffixMiscModifiersSection affix={affix} />
        </div>
      </div>
    </>
  );
};

export default AttachedAffixDetails;
