import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import { isNil } from 'lodash';
import React, { ReactNode, useEffect, useState } from 'react';

import { GuideWalkthroughInteractionSignal } from '../../../guide-quests/walkthrough/enums/guide-walkthrough-interaction-signal';
import { useGuideWalkthrough } from '../../../guide-quests/walkthrough/hooks/use-guide-walkthrough';
import { GuideTargets } from '../../../guide-quests/walkthrough/registry/guide-targets';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import InventoryStackBody from '../components/inventory-stack-body';
import { useEquipmentManagementRestriction } from '../hooks/use-equipment-management-restriction';
import { useGetInventoryItemDetails } from './api/hooks/use-get-inventory-item-details';
import { useProcessItemAction } from './api/hooks/use-process-item-action';
import AttachedAffixDetails from './attached-affix/attached-affix-details';
import AttachedHolyStacks from './attached-holy-stacks/attached-holy-stacks';
import InventoryItemActionButton from './inventory-item-action-button';
import ItemSkillTreeManager from './item-skills/item-skill-tree-manager';
import EquipItem from './partials/equip/equip-item';
import AffixesSection from './partials/item-view/affixes-section';
import AmbushCounterSection from './partials/item-view/ambush-and-counter-section';
import AttackSection from './partials/item-view/attack-section';
import DefenceSection from './partials/item-view/defence-section';
import HealingSection from './partials/item-view/healing-section';
import HolyStacksSection from './partials/item-view/holy-stacks-section';
import ItemMetaSection from './partials/item-view/item-meta-tsx';
import SocketsSection from './partials/item-view/sockets-section';
import StatsSection from './partials/item-view/stats-section';
import MoveToSet from './partials/move-to-set/move-to-set';
import InventoryItemProps from './types/inventory-item-props';
import { EquippableItemDetailsDefinition } from '../../../../api-definitions/items/equippable-item-definitions/equippable-item-details-definition';
import DuplicateGemAbilityWarning from '../../../../reusable-components/character-gem/duplicate-gem-ability-warning';
import { DuplicateGemAbilityWarningContext } from '../../../../reusable-components/character-gem/enums/duplicate-gem-ability-warning-context';
import { ItemActions } from '../../../../reusable-components/item/enums/item-actions';
import ItemAction from '../../../../reusable-components/item/item-action';
import ListItemOnMarket from '../../../../reusable-components/item/list-item-on-market';
import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { useGuideTarget } from '../../../guide-quests/walkthrough/hooks/use-guide-target';
import ItemDetailsStack from '../../item-details/components/item-details-stack';

import { GameDataError } from 'game-data/components/game-data-error';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const InventoryItem = ({
  slot_id,
  character_id,
  on_action,
  show_actions = true,
}: InventoryItemProps) => {
  const [itemAffixToView, setItemAffixToView] = useState<number | null>(null);
  const [shouldViewHolyStacks, setShouldViewHolyStacks] = useState(false);
  const [viewingEquip, setViewingEquip] = useState(false);
  const [listItem, setListItem] = useState(false);
  const [moveItem, setMoveItem] = useState(false);
  const [viewingItemSkills, setViewingItemSkills] = useState(false);
  const [duplicateItemId, setDuplicateItemId] = useState<number | null>(null);
  const [selectedItemAction, setSelectedItemAction] =
    useState<ItemActions | null>(null);
  const [isActionsMenuOpen, setIsActionsMenuOpen] = useState(false);
  const { completeTargetInteraction, registerTargetCleanup } =
    useGuideWalkthrough();
  const itemDetailsGuideTarget = useGuideTarget(GuideTargets.ITEM_DETAILS);
  const itemActionsGuideTarget = useGuideTarget(GuideTargets.ITEM_ACTIONS);
  const equipItemGuideTarget = useGuideTarget(GuideTargets.ITEM_EQUIP_ACTION);
  const equippedStateGuideTarget = useGuideTarget(
    GuideTargets.ITEM_EQUIPPED_STATE
  );

  useEffect(
    () =>
      registerTargetCleanup(GuideTargets.ITEM_ACTIONS, () => {
        setIsActionsMenuOpen(false);
      }),
    [registerTargetCleanup]
  );

  const { error, loading, data, refetch } = useGetInventoryItemDetails({
    character_id,
    slot_id,
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY_ITEM,
  });

  const {
    loading: processItemActionLoading,
    error: processItemActionError,
    setRequestData,
    resetError,
  } = useProcessItemAction();

  const {
    is_restricted: isEquipmentRestricted,
    restriction_message: equipmentRestrictionMessage,
  } = useEquipmentManagementRestriction();

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

  if (!('item_skills' in data)) {
    return (
      <div className="px-4">
        <GameDataError />
      </div>
    );
  }

  const item: EquippableItemDetailsDefinition = data;

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

  const handleEquipped = async (successMessage: string): Promise<void> => {
    setViewingEquip(false);
    await refetch();
    on_action(successMessage);
  };

  const handleActionSelected = (action: ItemActions) => {
    if (action === ItemActions.LIST) {
      setListItem(true);

      return;
    }

    if (action === ItemActions.MOVE_TO_SET) {
      setMoveItem(true);

      return;
    }

    setSelectedItemAction(action);
  };

  const handleActionsMenuOpenChange = (isOpen: boolean): void => {
    setIsActionsMenuOpen(isOpen);

    if (isOpen) {
      completeTargetInteraction(
        GuideTargets.ITEM_ACTIONS,
        GuideWalkthroughInteractionSignal.OPENED
      );
    }
  };

  const handleClosingActionConfirmation = () => {
    setSelectedItemAction(null);
  };

  const handleCloseListSection = () => {
    setListItem(false);
  };

  const handleCloseMoveToSet = () => {
    setMoveItem(false);
  };

  const handleItemActionConfirmation = (itemAction: ItemActions) => {
    setRequestData({
      character_id: character_id,
      item_id: data.item_id ?? 0,
      action_type: itemAction,
      on_success: on_action,
    });
  };

  const renderEquipItem = () => {
    if (!viewingEquip) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseViewEquip}
        aria_label="Equip Item"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody>
          <EquipItem
            character_id={character_id}
            slot_id={item.slot_id}
            item_to_equip_type={item.type}
            on_equip={(message) => void handleEquipped(message)}
          />
        </InventoryStackBody>
      </StackedCard>
    );
  };

  const renderMoveToSet = () => {
    if (!moveItem) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseMoveToSet}
        aria_label="Move To Set"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody>
          <div className="px-4">
            <MoveToSet
              character_id={character_id}
              item_slot_id={item.slot_id}
              on_action={on_action}
            />
          </div>
        </InventoryStackBody>
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

    if (!itemAffix) {
      return null;
    }

    return (
      <StackedCard
        on_close={handleCloseItemAffixView}
        aria_label="Affix Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
        close_button_data_guide_target={GuideTargets.ITEM_AFFIX_CLOSE}
      >
        <InventoryStackBody>
          <AttachedAffixDetails affix={itemAffix} />
        </InventoryStackBody>
      </StackedCard>
    );
  };

  const renderAttachedHolyStacksView = () => {
    if (!shouldViewHolyStacks || !item.applied_stacks) {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setShouldViewHolyStacks(false)}
        aria_label="Holy Stack Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody>
          <AttachedHolyStacks stacks={item.applied_stacks} />
        </InventoryStackBody>
      </StackedCard>
    );
  };

  const renderActions = () => {
    if (!show_actions) {
      return null;
    }

    return (
      <>
        {renderEquipmentRestriction()}
        {renderActionSection()}
        <Separator />
      </>
    );
  };

  const renderEquipmentRestriction = () => {
    if (equipmentRestrictionMessage === null) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        {equipmentRestrictionMessage}
      </Alert>
    );
  };

  const renderEquipState = () => {
    if (item.is_equipped) {
      return (
        <p
          role="status"
          className="font-semibold text-emerald-700 dark:text-emerald-400"
          {...equippedStateGuideTarget}
        >
          Equipped
        </p>
      );
    }

    return (
      <div
        className="flex justify-center sm:justify-end"
        {...(!isEquipmentRestricted ? equipItemGuideTarget : {})}
      >
        <Button
          on_click={handleViewEquip}
          label="Equip Item"
          variant={ButtonVariant.SUCCESS}
          disabled={isEquipmentRestricted}
        />
      </div>
    );
  };

  const renderActionSection = () => {
    if (processItemActionError) {
      return (
        <ApiErrorAlert
          apiError={processItemActionError.message}
          closable
          on_close={resetError}
        />
      );
    }

    if (listItem) {
      return (
        <ListItemOnMarket
          type={data.type}
          on_close={handleCloseListSection}
          character_id={character_id}
          slot_id={data?.slot_id || 0}
          on_action={on_action}
          min_list_price={data.min_list_price}
        />
      );
    }

    if (!isNil(selectedItemAction)) {
      return (
        <ItemAction
          item={item}
          action_type={selectedItemAction}
          on_confirmation={handleItemActionConfirmation}
          on_cancel={handleClosingActionConfirmation}
          processing={processItemActionLoading}
        />
      );
    }

    return (
      <div className="flex flex-col items-stretch gap-3 text-center sm:flex-row sm:items-center sm:justify-center sm:gap-6">
        {renderEquipState()}

        <div className="flex items-center justify-center gap-2">
          <span
            className="inline-block h-px w-10 bg-gray-300 dark:bg-gray-600"
            aria-hidden="true"
          />
          <span className="text-sm font-medium text-gray-500 dark:text-gray-400">
            Or
          </span>
          <span
            className="inline-block h-px w-10 bg-gray-300 dark:bg-gray-600"
            aria-hidden="true"
          />
        </div>

        <div
          className="flex justify-center sm:justify-start"
          {...itemActionsGuideTarget}
        >
          <InventoryItemActionButton
            on_select_action={handleActionSelected}
            is_open={isActionsMenuOpen}
            on_open_change={handleActionsMenuOpenChange}
          />
        </div>
      </div>
    );
  };

  const attack = Number(item.raw_damage ?? item.base_damage ?? 0);
  const ac = Number(item.raw_ac ?? item.base_ac ?? 0);
  const healing = Number(item.raw_healing ?? item.base_healing ?? 0);

  const sections: ReactNode[] = [
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
    <DuplicateGemAbilityWarning
      key="duplicate-gem-abilities"
      duplicates={item.duplicate_gem_abilities ?? []}
      context={DuplicateGemAbilityWarningContext.ITEM}
      on_open_item={setDuplicateItemId}
    />,
    <SocketsSection
      key="sockets"
      total={Number(item.socket_amount ?? 0)}
      sockets={item.sockets ?? []}
    />,
  ];

  const canViewItemSkills =
    item.type === InventoryItemTypes.ARTIFACT &&
    item.item_skills.length > 0 &&
    item.item_skill_progressions.length > 0;

  const renderDuplicateGemItemDetails = (): ReactNode => {
    if (duplicateItemId === null) {
      return null;
    }

    const duplicatesForItem = (item.duplicate_gem_abilities ?? []).filter(
      (duplicate) =>
        duplicate.items.some(
          (duplicateItem) => duplicateItem.item_id === duplicateItemId
        )
    );

    return (
      <ItemDetailsStack
        item_id={duplicateItemId}
        on_close={() => setDuplicateItemId(null)}
        duplicate_gem_abilities={duplicatesForItem}
      />
    );
  };

  const renderItemSkillTreeButton = (): ReactNode => {
    if (!canViewItemSkills) {
      return null;
    }

    return (
      <div className="flex justify-center pt-2">
        <Button
          on_click={() => setViewingItemSkills(true)}
          label="View Ancestral Skill Tree"
          aria_label="Ancestral Skill Tree"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  const renderItemSkillTree = (): ReactNode => {
    if (!viewingItemSkills || !canViewItemSkills) {
      return null;
    }

    return (
      <StackedCard
        on_close={() => setViewingItemSkills(false)}
        aria_label="Ancestral Skill Tree"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <InventoryStackBody>
          <ItemSkillTreeManager
            character_id={character_id}
            item={item}
            refetch={refetch}
          />
        </InventoryStackBody>
      </StackedCard>
    );
  };

  return (
    <>
      <div className="flex flex-col gap-4 px-4" {...itemDetailsGuideTarget}>
        <ItemMetaSection
          name={item.name}
          description={item.description}
          type={item.type}
          titleClassName={planeTextItemColors(item)}
        />
        <Separator />
        {renderActions()}

        <div className="space-y-4">
          {sections}
          {renderItemSkillTreeButton()}
        </div>
      </div>
      <AnimatePresence mode="wait">{renderEquipItem()}</AnimatePresence>
      <AnimatePresence mode="wait">{renderItemAffixView()}</AnimatePresence>
      <AnimatePresence mode="wait">{renderMoveToSet()}</AnimatePresence>
      <AnimatePresence mode="wait">
        {renderAttachedHolyStacksView()}
      </AnimatePresence>
      <AnimatePresence mode="wait">{renderItemSkillTree()}</AnimatePresence>
      <AnimatePresence mode="wait">
        {renderDuplicateGemItemDetails()}
      </AnimatePresence>
    </>
  );
};

export default InventoryItem;
