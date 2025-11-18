import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import { isNil } from 'lodash';
import React, { Fragment, ReactNode, useState } from 'react';

import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import AttachedAffixDetails from './attached-affix/attached-affix-details';
import AttachedHolyStacks from './attached-holy-stacks/attached-holy-stacks';
import InventoryItemActionButton from './inventory-item-action-button';
import EquipItem from './partials/equip/equip-item';
import AffixesSection from './partials/item-view/affixes-section';
import AmbushCounterSection from './partials/item-view/ambush-and-counter-section';
import AttackSection from './partials/item-view/attack-section';
import DefenceSection from './partials/item-view/defence-section';
import HealingSection from './partials/item-view/healing-section';
import HolyStacksSection from './partials/item-view/holy-stacks-section';
import ItemMetaSection from './partials/item-view/item-meta-tsx';
import StatsSection from './partials/item-view/stats-section';
import InventoryItemProps from './types/inventory-item-props';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventoryItem = ({
  slot_id,
  character_id,
  on_equip,
}: InventoryItemProps) => {
  const [itemAffixToView, setItemAffixToView] = useState<number | null>(null);
  const [shouldViewHolyStacks, setShouldViewHolyStacks] = useState(false);
  const [viewingEquip, setViewingEquip] = useState(false);

  const { error, loading, data } = useGetInventoryItemDetails({
    character_id,
    slot_id,
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM,
  });

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  if (isNil(data)) {
    return (
      <div className="px-4">
        <GameDataError />
      </div>
    );
  }

  const item = data as EquippableItemWithBase;

  const handleClickItemAffix = (affixId?: number) => {
    if (!affixId) {
      return;
    }

    setItemAffixToView(affixId);
  };

  const handleCloseItemAffixView = () => {
    setItemAffixToView(null);
  };

  const handleViewEquip = () => {
    setViewingEquip(true);
  };

  const handleCloseViewEquip = () => {
    setViewingEquip(false);
  };

  const renderEquipItem = () => {
    if (!viewingEquip) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseViewEquip}>
        <EquipItem
          character_id={character_id}
          slot_id={item.slot_id}
          item_to_equip_type={item.type}
          on_equip={on_equip}
        />
      </StackedCard>
    );
  };

  const renderItemAffixView = () => {
    if (!itemAffixToView) {
      return null;
    }

    let itemAffix = null;

    if (item?.item_suffix?.id === itemAffixToView) {
      itemAffix = item?.item_suffix;
    }

    if (item?.item_prefix?.id === itemAffixToView) {
      itemAffix = item?.item_prefix;
    }

    if (itemAffix) {
      return (
        <StackedCard on_close={handleCloseItemAffixView}>
          <AttachedAffixDetails affix={itemAffix} />
        </StackedCard>
      );
    }
  };

  const renderAttachedHolyStacksView = () => {
    if (!shouldViewHolyStacks || !item.applied_stacks) {
      return null;
    }

    return (
      <StackedCard on_close={() => setShouldViewHolyStacks(false)}>
        <AttachedHolyStacks stacks={item.applied_stacks} />
      </StackedCard>
    );
  };

  const attack = Number(item.raw_damage ?? item.base_damage ?? 0);
  const ac = Number(item.raw_ac ?? item.base_ac ?? 0);
  const healing = Number(item.raw_healing ?? item.base_healing ?? 0);

  const sectionsRaw: ReactNode[] = [
    <AffixesSection
      key="affixes"
      prefix={item.item_prefix}
      suffix={item.item_suffix}
      onOpenAffix={handleClickItemAffix}
    />,
    <AttackSection
      key="attack"
      attack={attack}
      baseDamageMod={item.base_damage_mod}
    />,
    <DefenceSection key="defence" ac={ac} baseAcMod={item.base_ac_mod} />,
    <HealingSection
      key="healing"
      healing={healing}
      baseHealingMod={item.base_healing_mod}
    />,
    <AmbushCounterSection
      key="ambush-counter"
      ambushChance={Number(item.ambush_chance ?? 0)}
      ambushResistChance={Number(item.ambush_resistance_chance ?? 0)}
      counterChance={Number(item.counter_chance ?? 0)}
      counterResistChance={Number(item.counter_resistance_chance ?? 0)}
    />,
    <StatsSection key="stats" item={item} />,
    <HolyStacksSection
      key="holy-stacks"
      total={Number(item.holy_stacks ?? 0)}
      applied={Number(item.holy_stacks_applied ?? 0)}
      attributeBonus={Number(item.holy_stack_stat_bonus ?? 0)}
      devouringDarknessBonus={Number(item.holy_stack_devouring_darkness ?? 0)}
      onClickApplied={() => setShouldViewHolyStacks(true)}
    />,
  ];

  const sections = sectionsRaw.filter(Boolean) as ReactNode[];

  return (
    <>
      <div className="flex flex-col gap-4 px-4">
        <ItemMetaSection
          name={item.name}
          description={item.description}
          type={item.type}
          titleClassName={planeTextItemColors(item)}
        />
        <Separator />
        <div className="flex flex-col items-stretch gap-3 text-center sm:flex-row sm:items-center sm:justify-center sm:gap-6">
          <div className="flex justify-center sm:justify-end">
            <Button
              on_click={handleViewEquip}
              label="Equip Item"
              variant={ButtonVariant.SUCCESS}
            />
          </div>

          <div className="flex items-center justify-center gap-2">
            <span className="inline-block h-px w-10 bg-gray-300" />
            <span className="text-sm font-medium text-gray-500">Or</span>
            <span className="inline-block h-px w-10 bg-gray-300" />
          </div>

          <div className="flex justify-center sm:justify-start">
            <InventoryItemActionButton />
          </div>
        </div>
        <Separator />

        <div className="space-y-4">
          {sections.map((section, index) => (
            <Fragment key={index}>{section}</Fragment>
          ))}
        </div>
      </div>
      <AnimatePresence mode="wait">{renderEquipItem()}</AnimatePresence>
      <AnimatePresence mode="wait">{renderItemAffixView()}</AnimatePresence>
      <AnimatePresence mode="wait">
        {renderAttachedHolyStacksView()}
      </AnimatePresence>
    </>
  );
};

export default InventoryItem;
