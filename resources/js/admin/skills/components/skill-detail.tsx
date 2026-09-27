import React, { ReactNode } from 'react';

import SkillDetailProps from './types/skill-detail-props';
import { skillTypeLabel } from '../enums/skill-type';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const formatOptionalValue = (value: number | null): string =>
  value === null ? 'None' : String(value);

const SkillDetail = ({ skill }: SkillDetailProps): ReactNode => (
  <Card>
    <p className="mb-4 text-gray-800 dark:text-gray-200">{skill.description}</p>

    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
      Basic
    </h3>
    <Dl>
      <Dt>Type</Dt>
      <Dd>{skillTypeLabel(skill.type)}</Dd>
      <Dt>Max Level</Dt>
      <Dd>{skill.max_level}</Dd>
      <Dt>Can Be Trained</Dt>
      <Dd>{skill.can_train ? 'Yes' : 'No'}</Dd>
      <Dt>Locked</Dt>
      <Dd>{skill.is_locked ? 'Yes' : 'No'}</Dd>
    </Dl>

    <Separator />

    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
      Character Modifiers
    </h3>
    <Dl>
      <Dt>Base Damage Modifier % per level</Dt>
      <Dd>{formatOptionalValue(skill.base_damage_mod_bonus_per_level)}</Dd>
      <Dt>Base Healing Modifier % per level</Dt>
      <Dd>{formatOptionalValue(skill.base_healing_mod_bonus_per_level)}</Dd>
      <Dt>Base AC Modifier % per level</Dt>
      <Dd>{formatOptionalValue(skill.base_ac_mod_bonus_per_level)}</Dd>
      <Dt>Skill Bonus Per Level</Dt>
      <Dd>{formatOptionalValue(skill.skill_bonus_per_level)}</Dd>
    </Dl>

    <Separator />

    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
      Class Bonus
    </h3>
    <Dl>
      <Dt>Belongs To Class</Dt>
      <Dd>{skill.game_class?.name ?? 'None'}</Dd>
      <Dt>Class Bonus % per level</Dt>
      <Dd>{formatOptionalValue(skill.class_bonus)}</Dd>
    </Dl>

    <Separator />

    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
      Timer Modifiers
    </h3>
    <Dl>
      <Dt>Fight Timeout Reduction % per level</Dt>
      <Dd>{formatOptionalValue(skill.fight_time_out_mod_bonus_per_level)}</Dd>
      <Dt>Move Timeout Reduction % per level</Dt>
      <Dd>{formatOptionalValue(skill.move_time_out_mod_bonus_per_level)}</Dd>
    </Dl>

    <Separator />

    <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
      Kingdom Modifiers
    </h3>
    <Dl>
      <Dt>Unit Recruitment Time Reduction %</Dt>
      <Dd>{formatOptionalValue(skill.unit_time_reduction)}</Dd>
      <Dt>Building Time Reduction %</Dt>
      <Dd>{formatOptionalValue(skill.building_time_reduction)}</Dd>
      <Dt>Unit Movement Time Reduction %</Dt>
      <Dd>{formatOptionalValue(skill.unit_movement_time_reduction)}</Dd>
    </Dl>
  </Card>
);

export default SkillDetail;
