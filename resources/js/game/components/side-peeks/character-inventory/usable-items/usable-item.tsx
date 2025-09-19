import React from 'react';

import UsableItemProps from './types/usable-item-props';
import DefinitionRow from '../../../../reusable-components/viewable-sections/definition-row';
import InfoLabel from '../../../../reusable-components/viewable-sections/info-label';
import Section from '../../../../reusable-components/viewable-sections/section';
import { formatPercent } from '../../../../util/format-number';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../inventory-item/partials/item-view/item-meta-tsx';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const UsableItem = ({ item, on_close }: UsableItemProps) => {
  const hasNumericValue = (value: unknown): value is number => {
    return typeof value === 'number' && !Number.isNaN(value);
  };

  const hasAnyNonZero = (values: Array<number | null | undefined>) => {
    return values.some(
      (value) => value !== null && value !== undefined && value !== 0
    );
  };

  const renderNumberRow = (label: string, value?: number | null) => {
    if (value === null || value === undefined) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="text-gray-800 dark:text-gray-200">{value}</span>
        }
      />
    );
  };

  const renderPercentRow = (label: string, percent?: number | null) => {
    if (percent === null || percent === undefined || percent === 0) {
      return null;
    }

    const proportion = percent / 100;

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {formatPercent(proportion)}
          </span>
        }
      />
    );
  };

  const renderYesNoRow = (label: string, value: boolean | null | undefined) => {
    if (value === null || value === undefined) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label={label} />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {value ? 'Yes' : 'No'}
          </span>
        }
      />
    );
  };

  const renderSkillsRow = () => {
    if (!Array.isArray(item.skills) || item.skills.length === 0) {
      return null;
    }

    return (
      <DefinitionRow
        left={<InfoLabel label="Affected Skills" />}
        right={
          <span className="text-gray-800 dark:text-gray-200">
            {item.skills.join(', ')}
          </span>
        }
      />
    );
  };

  const showOnlyHoly = hasNumericValue(item.holy_level) && item.holy_level > 0;
  const showOnlyKingdom = !showOnlyHoly && item.damages_kingdoms;

  const showModifiers =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    hasAnyNonZero([
      item.base_damage_mod_bonus,
      item.base_healing_mod_bonus,
      item.base_ac_mod_bonus,
      item.base_damage_mod,
      item.base_healing_mod,
      item.base_ac_mod,
    ]);

  const showMiscModifiers =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    hasAnyNonZero([
      item.fight_time_out_mod_bonus,
      item.move_time_out_mod_bonus,
    ]);

  const showSkillMods =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    (hasAnyNonZero([
      item.increase_skill_bonus_by,
      item.increase_skill_training_bonus_by,
    ]) ||
      (Array.isArray(item.skills) && item.skills.length > 0));

  const showGeneral =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    (hasNumericValue(item.lasts_for) || item.can_stack);

  const showStatIncrease =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    hasNumericValue(item.stat_increase) &&
    item.stat_increase > 0;

  const showExperienceSection =
    !showOnlyHoly &&
    !showOnlyKingdom &&
    (item.gain_additional_level ||
      (hasNumericValue(item.xp_bonus) && item.xp_bonus > 0));

  if (showOnlyHoly) {
    return (
      <>
        <div className="text-center p-4">
          <Button
            on_click={on_close}
            label="Close"
            variant={ButtonVariant.SUCCESS}
          />
        </div>

        <div className="px-4 flex flex-col gap-2">
          <ItemMetaSection
            name={item.name}
            description={item.description}
            type={item.type}
            titleClassName={planeTextItemColors(item)}
          />
          <Separator />
          <Section title="Holy Blessing" showSeparator={false}>
            {renderNumberRow('Holy Level', item.holy_level)}
          </Section>
        </div>
      </>
    );
  }

  if (showOnlyKingdom) {
    return (
      <>
        <div className="text-center p-4">
          <Button
            on_click={on_close}
            label="Close"
            variant={ButtonVariant.SUCCESS}
          />
        </div>

        <div className="px-4 flex flex-col gap-2">
          <ItemMetaSection
            name={item.name}
            description={item.description}
            type={item.type}
            titleClassName={planeTextItemColors(item)}
          />
          <Separator />
          <Section title="Kingdom Effects" showSeparator={false}>
            {renderYesNoRow('Damages Kingdoms', item.damages_kingdoms)}
            {renderNumberRow('Kingdom Damage', item.kingdom_damage)}
            {renderNumberRow('Lasts For (Minutes)', item.lasts_for)}
          </Section>
        </div>
      </>
    );
  }

  const buildGeneral = (showSeparator: boolean) => {
    if (!showGeneral) {
      return null;
    }

    return (
      <Section title="General" showSeparator={showSeparator}>
        {renderYesNoRow('Can Stack', item.can_stack)}
        {renderNumberRow('Lasts For (Minutes)', item.lasts_for)}
      </Section>
    );
  };

  const buildExperienceBonuses = (showSeparator: boolean) => {
    if (!showExperienceSection) {
      return null;
    }

    return (
      <Section title="Experience Bonuses" showSeparator={showSeparator}>
        {renderYesNoRow(
          'Gains Additional Levels on Level Up',
          item.gain_additional_level
        )}
        {hasNumericValue(item.xp_bonus) && item.xp_bonus > 0 ? (
          <DefinitionRow
            left={<InfoLabel label="XP Bonus" />}
            right={
              <span className="text-gray-800 dark:text-gray-200">
                {formatPercent(item.xp_bonus)}
              </span>
            }
          />
        ) : null}
      </Section>
    );
  };

  const buildStatIncrease = (showSeparator: boolean) => {
    if (!showStatIncrease) {
      return null;
    }

    return (
      <Section title="Stat Increase" showSeparator={showSeparator}>
        <DefinitionRow
          left={<InfoLabel label="Increases Stat by" />}
          right={
            <span className="text-gray-800 dark:text-gray-200">
              {formatPercent(item.stat_increase)}
            </span>
          }
        />
      </Section>
    );
  };

  const buildModifiers = (showSeparator: boolean) => {
    if (!showModifiers) {
      return null;
    }

    return (
      <Section title="Modifiers" showSeparator={showSeparator}>
        {renderNumberRow('Base Damage Mod (Bonus)', item.base_damage_mod_bonus)}
        {renderNumberRow(
          'Base Healing Mod (Bonus)',
          item.base_healing_mod_bonus
        )}
        {renderNumberRow('Base AC Mod (Bonus)', item.base_ac_mod_bonus)}
        {renderNumberRow('Base Damage Mod', item.base_damage_mod)}
        {renderNumberRow('Base Healing Mod', item.base_healing_mod)}
        {renderNumberRow('Base AC Mod', item.base_ac_mod)}
      </Section>
    );
  };

  const buildMiscModifiers = (showSeparator: boolean) => {
    if (!showMiscModifiers) {
      return null;
    }

    return (
      <Section title="Misc Modifiers" showSeparator={showSeparator}>
        {renderPercentRow(
          'Fight Timeout Modifier',
          item.fight_time_out_mod_bonus
        )}
        {renderPercentRow(
          'Move Timeout Modifier',
          item.move_time_out_mod_bonus
        )}
      </Section>
    );
  };

  const buildSkillMods = (showSeparator: boolean) => {
    if (!showSkillMods) {
      return null;
    }

    return (
      <Section title="Skill Modifiers" showSeparator={showSeparator}>
        {renderNumberRow(
          'Increase Skill Bonus By',
          item.increase_skill_bonus_by
        )}
        {renderNumberRow(
          'Increase Skill Training Bonus By',
          item.increase_skill_training_bonus_by
        )}
        {renderSkillsRow()}
      </Section>
    );
  };

  const builders = [
    buildGeneral,
    buildExperienceBonuses,
    buildStatIncrease,
    buildModifiers,
    buildMiscModifiers,
    buildSkillMods,
  ];
  const enabledBuilders = builders.filter((build) => build(true) != null);
  const lastIndex = enabledBuilders.length - 1;

  return (
    <>
      <div className="text-center p-4">
        <Button
          on_click={on_close}
          label="Close"
          variant={ButtonVariant.SUCCESS}
        />
      </div>

      <div className="px-4 flex flex-col gap-2">
        <ItemMetaSection
          name={item.name}
          description={item.description}
          type={item.type}
          titleClassName={planeTextItemColors(item)}
        />
        <Separator />
        {enabledBuilders.map((build, index) => build(index !== lastIndex))}
      </div>
    </>
  );
};

export default UsableItem;
