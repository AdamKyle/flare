import React, { Fragment } from 'react';

import SkillsSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/skill-section-props';
import SkillSummary from '../skill-summary';

import Separator from 'ui/separator/separator';

const SkillsSection = ({ adjustments }: SkillsSectionProps) => {
  if (
    !Array.isArray(adjustments.skill_summary) ||
    adjustments.skill_summary.length <= 0
  ) {
    return null;
  }

  return (
    <Fragment>
      <h4 className="text-mango-tango-500 dark:text-mango-tango-300 mt-3 mb-1 text-xs font-semibold tracking-wide uppercase">
        Skills
      </h4>
      <Separator />
      <SkillSummary adjustments={adjustments} />
    </Fragment>
  );
};

export default SkillsSection;
