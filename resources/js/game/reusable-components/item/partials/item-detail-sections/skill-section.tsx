import React from 'react';

import { Detail } from '../../../../api-definitions/items/item-comparison-details';
import ItemDetails from '../../../../api-definitions/items/item-details';
import BaseSectionProps from '../../types/partials/item-detail-sections/base-section-props';
import ItemDetailSection from '../item-detail-section';

import Dl from 'ui/dl/dl';
import Separator from 'ui/separator/separator';

const SkillSection = ({ item, is_adjustment }: BaseSectionProps) => {
  const hasAttribute = (item: ItemDetails | Detail): item is Detail => {
    return 'skills' in item;
  };

  if (!hasAttribute(item)) {
    return null;
  }

  const skills = item.skills.map((skill) => ({
    label: skill.skill_name,
    value: skill.skill_bonus,
  }));

  return (
    <div>
      <h4 className="mb-2 text-sm font-semibold text-danube-600 dark:text-danube-300">
        Skill(s) Adjustment
      </h4>
      <Separator />
      <Dl>
        {skills.map(({ label, value }) => (
          <ItemDetailSection
            key={label}
            label={label}
            item_type={item.type}
            value={value}
            is_adjustment={is_adjustment}
          />
        ))}
      </Dl>
    </div>
  );
};

export default SkillSection;
