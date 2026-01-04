import React from 'react';

import AncestralItemSkillSectionProps from './types/ancestral-item-skill-section-props';

const AncestralItemSkillSection = ({
  ancestral_item_skill_data,
}: AncestralItemSkillSectionProps) => {
  if (!ancestral_item_skill_data || ancestral_item_skill_data.length <= 0) {
    return null;
  }

  const listElements = ancestral_item_skill_data.map((ancestralSkillInfo) => {
    return (
      <li>
        <strong>{ancestralSkillInfo.name}</strong>{' '}
        <span className="text-green-700 dark:text-green-500">
          +{(ancestralSkillInfo.increase_amount * 100).toFixed(2)}%
        </span>
      </li>
    );
  });

  return (
    <div className={'my-2'}>
      <h4 className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}>
        Ancestral Item Skill Effects
      </h4>
      <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
        {listElements}
      </ol>
    </div>
  );
};

export default AncestralItemSkillSection;
