import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import React, { ReactNode, useState } from 'react';

import ItemDetailsStack from './item-details-stack';
import ItemDetailsBodyProps from './types/item-details-body-props';
import DuplicateGemAbilityWarning from '../../../../reusable-components/character-gem/duplicate-gem-ability-warning';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import AmbushCounterSection from '../../character-inventory/inventory-item/partials/item-view/ambush-and-counter-section';
import AttackSection from '../../character-inventory/inventory-item/partials/item-view/attack-section';
import DefenceSection from '../../character-inventory/inventory-item/partials/item-view/defence-section';
import HealingSection from '../../character-inventory/inventory-item/partials/item-view/healing-section';
import ItemMetaSection from '../../character-inventory/inventory-item/partials/item-view/item-meta-tsx';
import SocketsSection from '../../character-inventory/inventory-item/partials/item-view/sockets-section';
import StatsSection from '../../character-inventory/inventory-item/partials/item-view/stats-section';
import { useItemDetails } from '../api/hooks/use-item-details';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const ItemDetailsBody = ({
  item_id,
  duplicate_gem_abilities,
}: ItemDetailsBodyProps): ReactNode => {
  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const { data, loading, error } = useItemDetails(item_id);

  if (loading) {
    return (
      <div className="px-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="px-4">
        <ApiErrorAlert apiError={error ?? 'Unable to load item details.'} />
      </div>
    );
  }

  const attack = Number(data.raw_damage ?? 0);
  const ac = Number(data.raw_ac ?? data.base_ac ?? 0);
  const healing = Number(data.raw_healing ?? data.base_healing ?? 0);
  const baseDamageMod = Number(data.base_damage_mod ?? 0);
  const baseAcMod = Number(data.base_ac_mod ?? 0);
  const baseHealingMod = Number(data.base_healing_mod ?? 0);
  const ambushChance = Number(data.ambush_chance ?? 0);
  const ambushResistChance = Number(data.ambush_resistance_chance ?? 0);
  const counterChance = Number(data.counter_chance ?? 0);
  const counterResistChance = Number(data.counter_resistance_chance ?? 0);
  const resolvedDuplicateGemAbilities =
    duplicate_gem_abilities ?? data.duplicate_gem_abilities ?? [];

  return (
    <>
      <div className="flex flex-col gap-4 px-4">
        <ItemMetaSection
          name={data.name}
          description={data.description}
          type={data.type}
          titleClassName={planeTextItemColors(data)}
        />

        <Separator />

        <div className="space-y-4">
          <AttackSection attack={attack} baseDamageMod={baseDamageMod} />
          <DefenceSection ac={ac} baseAcMod={baseAcMod} />
          <HealingSection healing={healing} baseHealingMod={baseHealingMod} />
          <AmbushCounterSection
            ambushChance={ambushChance}
            ambushResistChance={ambushResistChance}
            counterChance={counterChance}
            counterResistChance={counterResistChance}
          />
          <StatsSection item={data} />
          <DuplicateGemAbilityWarning
            duplicates={resolvedDuplicateGemAbilities}
            context="item"
            on_open_item={setSelectedItemId}
          />
          <SocketsSection
            total={Number(data.socket_amount ?? 0)}
            sockets={data.sockets ?? []}
          />
        </div>
      </div>
      <AnimatePresence mode="wait">
        {selectedItemId === null ? null : (
          <ItemDetailsStack
            item_id={selectedItemId}
            on_close={() => setSelectedItemId(null)}
            duplicate_gem_abilities={resolvedDuplicateGemAbilities.filter(
              (duplicate) =>
                duplicate.items.some(
                  (duplicateItem) => duplicateItem.item_id === selectedItemId
                )
            )}
          />
        )}
      </AnimatePresence>
    </>
  );
};

export default ItemDetailsBody;
