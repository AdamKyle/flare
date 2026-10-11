import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CharacterActiveBoonIndicator from './character-active-boon-indicator';
import { useCharacterActiveBoonStatus } from './hooks/use-character-active-boon-status';
import { useActivityActions } from '../../../activity/hooks/use-activity-actions';
import { useOpenActivitySidePeek } from '../../../activity/hooks/use-open-activity-side-peek';
import { GuideTargets } from '../../../guide-quests/walkthrough/registry/guide-targets';
import { useManageQuestLogVisibility } from '../../../quests/hooks/use-manage-quest-log-visibility';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../floating-cards/map-section/hooks/use-manage-shop-visibility';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import IconContainer from 'ui/icon-container/icon-container';

export const IconSection = (): ReactNode => {
  const { openCharacterCard, showCharacterCard } =
    useManageCharacterCardVisibility();

  const { openCraftingCard } = useManageCraftingCardVisibility();

  const { openMapCard } = useManageMapSectionVisibility();

  const { openShop } = useManageShopVisibility();

  const { openQuestLog } = useManageQuestLogVisibility();

  const { has_active_boons: hasActiveBoons } = useCharacterActiveBoonStatus();

  const { open_activity } = useOpenActivitySidePeek();

  const { has_activity_attention } = useActivityActions();

  const activityAriaLabel = has_activity_attention
    ? 'Activity, attention required'
    : 'Activity';

  return (
    <IconContainer>
      <IconButton
        label="Character"
        icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openCharacterCard}
        additional_css="w-full lg:w-auto"
        status_indicator={
          <CharacterActiveBoonIndicator active={hasActiveBoons} />
        }
        data_guide_target={GuideTargets.CHARACTER_OPEN}
        data_guide_target_complete={showCharacterCard}
      />
      <IconButton
        label="Craft"
        icon={<i className="ra ra-anvil text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openCraftingCard}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Quests"
        icon={<i className="far fa-comments text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openQuestLog}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Map"
        icon={<i className="ra ra-compass text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openMapCard}
        additional_css="w-full lg:w-auto"
        data_guide_target={GuideTargets.MAP_OPEN}
      />
      <IconButton
        label="Shop"
        icon={<i className="fas fa-store text-sm" aria-hidden="true"></i>}
        variant={ButtonVariant.PRIMARY}
        on_click={openShop}
        additional_css="w-full lg:w-auto"
      />
      <IconButton
        label="Activity"
        icon={
          <i
            className={clsx('far fa-bell text-sm', {
              'text-mango-tango-600 dark:text-mango-tango-400 animate-pulse motion-reduce:animate-none':
                has_activity_attention,
            })}
            aria-hidden="true"
          ></i>
        }
        variant={ButtonVariant.PRIMARY}
        on_click={open_activity}
        additional_css="w-full lg:w-auto"
        aria_label={activityAriaLabel}
      />
    </IconContainer>
  );
};
