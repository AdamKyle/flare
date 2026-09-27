import React, { ReactNode } from 'react';

import PassiveSkillDetailProps from './types/passive-skill-detail-props';
import PassiveSkillCard from '../../../game/reusable-components/passive-skill/components/passive-skill-card';
import PassiveSkillChildDefinition from '../api/definitions/passive-skill-child-definition';
import { passiveSkillEffectLabel } from '../enums/passive-skill-effect';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import Separator from 'ui/separator/separator';

const PassiveSkillDetail = ({
  passive_skill: passiveSkill,
  on_open_passive_skill: onOpenPassiveSkill,
}: PassiveSkillDetailProps): ReactNode => {
  const progressiveChildren = useProgressiveList({
    total_items: passiveSkill.child_skills.length,
    initial_count: 5,
    batch_size: 5,
    reset_key: passiveSkill.id,
  });

  const renderChildSkill = (
    childSkill: PassiveSkillChildDefinition
  ): ReactNode => (
    <li key={childSkill.id}>
      <PassiveSkillCard
        passive_skill_id={childSkill.id}
        name={childSkill.name}
        effect_label={passiveSkillEffectLabel(childSkill.effect_type)}
        unlocks_at_level={childSkill.unlocks_at_level}
        max_level={childSkill.max_level}
        on_open_passive_skill={onOpenPassiveSkill}
      />
    </li>
  );

  const renderParent = (): ReactNode => {
    if (!passiveSkill.parent) {
      return null;
    }

    return (
      <PassiveSkillCard
        passive_skill_id={passiveSkill.parent.id}
        name={passiveSkill.parent.name}
        effect_label={passiveSkillEffectLabel(passiveSkill.parent.effect_type)}
        max_level={passiveSkill.parent.max_level}
        on_open_passive_skill={onOpenPassiveSkill}
      />
    );
  };

  const renderChildSkills = (): ReactNode => {
    if (passiveSkill.child_skills.length === 0) {
      return (
        <p className="text-gray-700 dark:text-gray-300">
          No Passive Skills belong to this Skill.
        </p>
      );
    }

    const visibleChildren = passiveSkill.child_skills.slice(
      0,
      progressiveChildren.visible_count
    );

    return (
      <>
        <InfiniteScroll
          handle_scroll={progressiveChildren.handle_scroll}
          additional_css="max-h-64"
        >
          <ul className="space-y-2">{visibleChildren.map(renderChildSkill)}</ul>
        </InfiniteScroll>
        <p
          className="mt-2 text-sm text-gray-600 dark:text-gray-400"
          role="status"
        >
          Showing {visibleChildren.length} of {passiveSkill.child_skills.length}{' '}
          Passive Skills.
        </p>
      </>
    );
  };

  return (
    <Card>
      {passiveSkill.description && (
        <p className="mb-4 text-gray-800 dark:text-gray-200">
          {passiveSkill.description}
        </p>
      )}
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Basic
      </h3>
      <Dl>
        <Dt>Effect</Dt>
        <Dd>{passiveSkillEffectLabel(passiveSkill.effect_type)}</Dd>
        <Dt>Max Level</Dt>
        <Dd>{passiveSkill.max_level}</Dd>
        <Dt>Hours Per Level</Dt>
        <Dd>{passiveSkill.hours_per_level}</Dd>
      </Dl>

      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Tree / Unlock
      </h3>
      {renderParent()}
      {typeof passiveSkill.unlocks_at_level === 'number' &&
        passiveSkill.unlocks_at_level > 0 && (
          <p className="mt-3 text-sm text-gray-800 dark:text-gray-200">
            Unlocks At Level: {passiveSkill.unlocks_at_level}
          </p>
        )}
      <h4 className="mt-4 mb-2 font-semibold text-gray-900 dark:text-gray-100">
        Child Skills
      </h4>
      {renderChildSkills()}
    </Card>
  );
};

export default PassiveSkillDetail;
