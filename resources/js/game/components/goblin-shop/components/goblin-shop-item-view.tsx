import React from 'react';

import GoblinShopCostView from './goblin-shop-cost-view';
import { planeTextItemColors } from '../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ExperienceBonusesSection from '../../side-peeks/character-inventory/usable-items/partials/experience-bonus-section';
import GeneralSection from '../../side-peeks/character-inventory/usable-items/partials/general-section';
import HolyOilSection from '../../side-peeks/character-inventory/usable-items/partials/holy-oil-section';
import KingdomEffectsSection from '../../side-peeks/character-inventory/usable-items/partials/kingdom-effects-section';
import MiscModifiersSection from '../../side-peeks/character-inventory/usable-items/partials/mic-modifiers-section';
import ModifiersSection from '../../side-peeks/character-inventory/usable-items/partials/modifier-section';
import SkillModifiersSection from '../../side-peeks/character-inventory/usable-items/partials/skill-modifiers-section';
import StatIncreaseSection from '../../side-peeks/character-inventory/usable-items/partials/stat-increase-section';
import GoblinShopItemViewProps from '../types/goblin-shop-item-preview-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Separator from 'ui/separator/separator';

const GoblinShopItemView = ({ item, on_close }: GoblinShopItemViewProps) => {
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
      factories.push(() => (
        <GeneralSection
          key="gen"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }

    if (shouldShowExperienceBonuses) {
      factories.push(() => (
        <ExperienceBonusesSection
          key="xp"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }

    if (shouldShowStatIncrease) {
      factories.push(() => (
        <StatIncreaseSection
          key="stat"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }

    if (shouldShowCoreModifiers) {
      factories.push(() => (
        <ModifiersSection
          key="mods"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }

    if (shouldShowMiscModifiers) {
      factories.push(() => (
        <MiscModifiersSection
          key="misc"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }

    if (shouldShowSkillModifiers) {
      factories.push(() => (
        <SkillModifiersSection
          key="skills"
          item={item}
          showSeparator={false}
          showTitleSeparator
        />
      ));
    }
  }

  if (item.damages_kingdoms) {
    factories.push(() => (
      <KingdomEffectsSection key="king" item={item} showTitleSeparator />
    ));
  }

  if (item.holy_level != null) {
    factories.push(() => (
      <HolyOilSection key="holy" item={item} showTitleSeparator />
    ));
  }

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title={`Viewing: ${item.name}`}
    >
      <Card>
        <div className="w-full">
          <div className="mb-2">
            <h2
              className={`text-xl font-semibold break-words ${planeTextItemColors(item as never)}`}
            >
              {item.name}
            </h2>
            <p className="mt-2 text-gray-700 dark:text-gray-300 break-words">
              {item.description}
            </p>
          </div>
          <Separator />

          <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <GoblinShopCostView item={item} />
            {factories.map((renderSection) => renderSection(false))}
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default GoblinShopItemView;
