import React from 'react';

import UsableItemEffectsProps from './types/usable-item-effects-props';
import ExperienceBonusesSection from '../../components/side-peeks/character-inventory/usable-items/partials/experience-bonus-section';
import GeneralSection from '../../components/side-peeks/character-inventory/usable-items/partials/general-section';
import HolyOilSection from '../../components/side-peeks/character-inventory/usable-items/partials/holy-oil-section';
import KingdomEffectsSection from '../../components/side-peeks/character-inventory/usable-items/partials/kingdom-effects-section';
import MiscModifiersSection from '../../components/side-peeks/character-inventory/usable-items/partials/mic-modifiers-section';
import ModifiersSection from '../../components/side-peeks/character-inventory/usable-items/partials/modifier-section';
import SkillModifiersSection from '../../components/side-peeks/character-inventory/usable-items/partials/skill-modifiers-section';
import StatIncreaseSection from '../../components/side-peeks/character-inventory/usable-items/partials/stat-increase-section';

const UsableItemEffects = ({ item }: UsableItemEffectsProps) => {
  const factories: Array<
    (showSeparator: boolean) => React.ReactElement | null
  > = [];

  if (item.usable) {
    const shouldShowGeneral =
      (item.lasts_for != null && item.lasts_for > 0) || item.can_stack;

    const shouldShowExperienceBonuses =
      item.gain_additional_level || (item.xp_bonus ?? 0) > 0;

    const shouldShowStatIncrease = item.stat_increase > 0;

    const shouldShowCoreModifiers =
      (item.base_damage_mod ?? 0) > 0 ||
      (item.base_healing_mod ?? 0) > 0 ||
      (item.base_ac_mod ?? 0) > 0;

    const shouldShowMiscModifiers =
      (item.fight_time_out_mod_bonus ?? 0) > 0 ||
      (item.move_time_out_mod_bonus ?? 0) > 0;

    const shouldShowSkillModifiers =
      (item.increase_skill_bonus_by ?? 0) > 0 ||
      (item.increase_skill_training_bonus_by ?? 0) > 0 ||
      (Array.isArray(item.skills) && item.skills.length > 0);

    if (shouldShowGeneral) {
      factories.push((showSeparator) => (
        <GeneralSection key="gen" item={item} showSeparator={showSeparator} />
      ));
    }

    if (shouldShowExperienceBonuses) {
      factories.push((showSeparator) => (
        <ExperienceBonusesSection
          key="xp"
          item={item}
          showSeparator={showSeparator}
        />
      ));
    }

    if (shouldShowStatIncrease) {
      factories.push((showSeparator) => (
        <StatIncreaseSection
          key="stat"
          item={item}
          showSeparator={showSeparator}
        />
      ));
    }

    if (shouldShowCoreModifiers) {
      factories.push((showSeparator) => (
        <ModifiersSection
          key="mods"
          item={item}
          showSeparator={showSeparator}
        />
      ));
    }

    if (shouldShowMiscModifiers) {
      factories.push((showSeparator) => (
        <MiscModifiersSection
          key="misc"
          item={item}
          showSeparator={showSeparator}
        />
      ));
    }

    if (shouldShowSkillModifiers) {
      factories.push((showSeparator) => (
        <SkillModifiersSection
          key="skills"
          item={item}
          showSeparator={showSeparator}
        />
      ));
    }
  }

  if (item.damages_kingdoms) {
    factories.push(() => <KingdomEffectsSection key="king" item={item} />);
  }

  if (item.holy_level != null) {
    factories.push(() => <HolyOilSection key="holy" item={item} />);
  }

  const lastIndex = factories.length - 1;

  return (
    <>
      {factories.map((renderSection, index) =>
        renderSection(index !== lastIndex)
      )}
    </>
  );
};

export default UsableItemEffects;
