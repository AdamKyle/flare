import { isNil } from 'lodash';
import React, { Fragment, ReactNode, useState } from 'react';

import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import AttachedAffixDetails from './attached-affix/attached-affix-details';
import AffixesSection from './partials/item-view/affixes-section';
import AmbushCounterSection from './partials/item-view/ambush-and-counter-section';
import AttackSection from './partials/item-view/attack-section';
import DefenceSection from './partials/item-view/defence-section';
import HealingSection from './partials/item-view/healing-section';
import HolyStacksSection from './partials/item-view/holy-stacks-section';
import ItemMetaSection from './partials/item-view/item-meta-tsx';
import StatsSection from './partials/item-view/stats-section';
import InventoryItemProps from './types/inventory-item-props';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventoryItem = ({
  item_id,
  character_id,
  close_item_view,
}: InventoryItemProps) => {
  const [itemAffixToView, setItemAffixToView] = useState<number | null>(null);

  const { error, loading, data } = useGetInventoryItemDetails({
    character_id,
    item_id,
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
    return null;
  }

  if (isNil(data)) {
    return (
      <div className="px-4">
        <GameDataError />
      </div>
    );
  }

  const item = data;

  const handleClickItemAffix = (affixId?: number) => {
    if (!affixId) {
      return;
    }

    setItemAffixToView(affixId);
  };

  const handleCloseItemAffixView = () => {
    setItemAffixToView(null);
  };

  if (itemAffixToView) {
    let itemAffix = null;

    if (item?.item_suffix?.id === itemAffixToView) {
      itemAffix = item?.item_suffix;
    }

    if (item?.item_prefix?.id === itemAffixToView) {
      itemAffix = item?.item_prefix;
    }

    if (itemAffix) {
      return (
        <AttachedAffixDetails
          affix={itemAffix}
          on_close={handleCloseItemAffixView}
        />
      );
    }
  }

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
    />,
  ];

  const sections = sectionsRaw.filter(Boolean) as ReactNode[];

  return (
    <>
      <div className="text-center p-4">
        <Button
          on_click={close_item_view}
          label="Close"
          variant={ButtonVariant.SUCCESS}
        />
      </div>

      <div className="px-4 flex flex-col gap-4">
        <ItemMetaSection
          name={item.name}
          description={item.description}
          type={item.type}
          titleClassName={planeTextItemColors(item)}
        />

        <Separator />

        <div className="space-y-4">
          {sections.map((section, index) => (
            <Fragment key={index}>{section}</Fragment>
          ))}
        </div>
      </div>
    </>
  );
};

export default InventoryItem;
