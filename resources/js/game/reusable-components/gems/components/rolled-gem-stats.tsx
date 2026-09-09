import React, { Fragment, ReactNode } from 'react';

import { gemTypeLabel } from '../../../../admin/shared/enums/gem-type';
import RolledGemStatsProps from '../types/rolled-gem-stats-props';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

/**
 * Permission-neutral factual presentation of one concrete rolled Gem's
 * non-zero stat values. Owns no API, authorization, activation, or
 * Character state so it can be reused by Admin and future Player contexts.
 */
const RolledGemStats = ({
  roll,
  display_groups: displayGroups,
}: RolledGemStatsProps): ReactNode => {
  const hasIdentityDetails =
    roll.crafting_skills.length > 0 || roll.monster_atonement !== null;

  const visibleGroups = displayGroups
    .map((group) => ({
      title: group.title,
      fields: group.fields
        .map((field) => ({ field, value: roll[field.rolled_field] }))
        .filter(
          (entry): entry is { field: typeof entry.field; value: number } =>
            typeof entry.value === 'number' && entry.value > 0
        ),
    }))
    .filter((group) => group.fields.length > 0);

  const renderIdentity = (): ReactNode => {
    if (!hasIdentityDetails) {
      return null;
    }

    return (
      <Dl>
        {roll.crafting_skills.length > 0 && (
          <Fragment>
            <Dt>Crafting Skills</Dt>
            <Dd>
              {roll.crafting_skills.map((skill) => skill.name).join(', ')}
            </Dd>
          </Fragment>
        )}
        {roll.monster_atonement !== null && (
          <Fragment>
            <Dt>Monster Atonement</Dt>
            <Dd>{gemTypeLabel(roll.monster_atonement)}</Dd>
            {roll.monster_atonement_amount !== null &&
              roll.monster_atonement_amount > 0 && (
                <Fragment>
                  <Dt>Monster Atonement Amount</Dt>
                  <Dd>{formatPercent(roll.monster_atonement_amount)}</Dd>
                </Fragment>
              )}
          </Fragment>
        )}
      </Dl>
    );
  };

  const renderGroups = (): ReactNode => {
    let hasRenderedGroup = false;

    return visibleGroups.map((group) => {
      const showSeparator = hasIdentityDetails || hasRenderedGroup;
      hasRenderedGroup = true;

      return (
        <Fragment key={group.title}>
          {showSeparator && <Separator />}
          <div>
            <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
              {group.title}
            </h4>
            <Dl>
              {group.fields.map(({ field, value }) => (
                <Fragment key={field.rolled_field}>
                  <Dt>{field.label}</Dt>
                  <Dd>{formatPercent(value)}</Dd>
                </Fragment>
              ))}
            </Dl>
          </div>
        </Fragment>
      );
    });
  };

  return (
    <div className="flex flex-col gap-3">
      {renderIdentity()}
      {renderGroups()}
    </div>
  );
};

export default RolledGemStats;
