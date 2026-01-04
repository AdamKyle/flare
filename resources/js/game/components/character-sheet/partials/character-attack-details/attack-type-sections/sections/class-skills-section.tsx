import React from 'react';

import ClassSkillsSectionProps from './types/class-skills-section-props';

const ClassSkillsSection = ({ class_skills }: ClassSkillsSectionProps) => {
  if (!class_skills || class_skills.length <= 0) {
    return null;
  }

  const listElements = class_skills.map((classSkill) => {
    return (
      <li>
        <strong>{classSkill.name}</strong>{' '}
        <span className="text-green-700 dark:text-green-500">
          +{(classSkill.increase_amount * 100).toFixed(2)}%
        </span>
      </li>
    );
  });

  return (
    <div className={'my-2'}>
      <h4 className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}>
        Effecting Class Skills
      </h4>
      <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
        {listElements}
      </ol>
    </div>
  );
};

export default ClassSkillsSection;
