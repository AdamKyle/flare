import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const MonsterSkillsSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Skills
      </h3>
      <Separator />
      <Dl>
        <Dt>Accuracy:</Dt>
        <Dd>{formatPercent(monster.accuracy)}</Dd>

        <Dt>Casting Accuracy:</Dt>
        <Dd>{formatPercent(monster.casting_accuracy)}</Dd>

        <Dt>Dodge:</Dt>
        <Dd>{formatPercent(monster.dodge)}</Dd>

        <Dt>Criticality:</Dt>
        <Dd>{formatPercent(monster.criticality)}</Dd>
      </Dl>
    </>
  );
};

export default MonsterSkillsSection;
