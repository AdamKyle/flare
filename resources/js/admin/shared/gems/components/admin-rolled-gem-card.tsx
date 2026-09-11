import React, { Fragment, ReactNode } from 'react';

import AdminRolledGemCardProps from './types/admin-rolled-gem-card-props';
import { gemTypeLabel } from '../../../../game/reusable-components/gems/enums/gem-type';

import { formatPercent } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Shared Admin presentation card for one actual, concrete Gem roll (as
 * opposed to profile min/max configuration). Used for the profile Active
 * Roll section, Roll History entries, and Bulk Roll result feeds for both
 * Map Gems and Location Gems.
 */
const AdminRolledGemCard = ({
  roll,
  source_label: sourceLabel,
  source_name: sourceName,
  display_groups: displayGroups,
  profile_name: profileName,
  on_activate: onActivate,
  activating,
}: AdminRolledGemCardProps): ReactNode => {
  const renderHeader = (): ReactNode => (
    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        {profileName && (
          <p className="text-mulled-wine-700 dark:text-mulled-wine-300 text-sm font-medium">
            {profileName}
          </p>
        )}
        <h3 className="text-mulled-wine-900 dark:text-mulled-wine-100 text-lg font-semibold">
          Roll #{roll.roll_number}
        </h3>
        <p className="text-mulled-wine-700 dark:text-mulled-wine-300 text-sm">
          {sourceLabel}: {sourceName}
        </p>
      </div>
      {renderActionOrBadge()}
    </div>
  );

  const renderActionOrBadge = (): ReactNode => {
    if (roll.is_active) {
      return (
        <span className="w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
          Active
        </span>
      );
    }

    if (!onActivate) {
      return null;
    }

    return (
      <Button
        label={activating ? 'Activating…' : 'Make Active'}
        variant={ButtonVariant.PRIMARY}
        additional_css="text-sm px-3 py-1.5 w-fit"
        on_click={onActivate}
        disabled={activating}
      />
    );
  };

  const renderCraftingSkills = (): ReactNode => {
    if (roll.crafting_skills.length === 0) {
      return null;
    }

    return (
      <Fragment>
        <Dt>Crafting Skills</Dt>
        <Dd>{roll.crafting_skills.map((skill) => skill.name).join(', ')}</Dd>
      </Fragment>
    );
  };

  const renderMonsterAtonement = (): ReactNode => {
    if (roll.monster_atonement === null) {
      return null;
    }

    return (
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
    );
  };

  const hasIdentityDetails =
    roll.crafting_skills.length > 0 || roll.monster_atonement !== null;

  const renderGroups = (): ReactNode =>
    displayGroups.map((group) => {
      const visibleFields = group.fields
        .map((field) => ({ field, value: roll[field.rolled_field] }))
        .filter(
          (entry): entry is { field: typeof entry.field; value: number } =>
            typeof entry.value === 'number' && entry.value > 0
        );

      if (visibleFields.length === 0) {
        return null;
      }

      return (
        <div key={group.title} className="mt-4">
          <h4 className="text-mulled-wine-800 dark:text-mulled-wine-200 mb-2 text-sm font-semibold">
            {group.title}
          </h4>
          <Dl>
            {visibleFields.map(({ field, value }) => (
              <Fragment key={field.rolled_field}>
                <Dt>{field.label}</Dt>
                <Dd>{formatPercent(value)}</Dd>
              </Fragment>
            ))}
          </Dl>
        </div>
      );
    });

  return (
    <div className="border-mulled-wine-300 bg-mulled-wine-50 dark:border-mulled-wine-700 dark:bg-mulled-wine-950 rounded-sm border p-3 md:p-4">
      {renderHeader()}
      {hasIdentityDetails && (
        <Dl>
          {renderCraftingSkills()}
          {renderMonsterAtonement()}
        </Dl>
      )}
      {renderGroups()}
    </div>
  );
};

export default AdminRolledGemCard;
