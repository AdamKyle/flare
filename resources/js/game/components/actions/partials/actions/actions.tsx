import clsx from 'clsx';
import React, { ReactNode } from 'react';

import ActionSection from './action-section';
import { useIsMobile } from './hooks/use-is-mobile';
import NavigationActionsComponent from './navigation-actions';
import ActionsProps from './types/actions-props';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../floating-cards/map-section/hooks/use-manage-shop-visibility';
import MonsterActions from '../monster-section/monster-actions';

import Card from 'ui/cards/card';

const Actions = (props: ActionsProps): ReactNode => {
  const { isMobile } = useIsMobile();
  const { showMonsterStats } = props;

  const { showCharacterCard } = useManageCharacterCardVisibility();

  const { showCraftingCard } = useManageCraftingCardVisibility();

  const { showMapCard } = useManageMapSectionVisibility();

  const { showShopCard } = useManageShopVisibility();

  const isShowingSideSection = (): boolean => {
    return showCharacterCard || showCraftingCard || showMapCard || showShopCard;
  };

  const isNotShowingSideSection = (): boolean => {
    return (
      !showCraftingCard && !showCharacterCard && !showMapCard && !showShopCard
    );
  };

  const renderActionSection = () => {
    if (isMobile && isShowingSideSection()) {
      return null;
    }

    return (
      <MonsterActions
        is_showing_side_section={isShowingSideSection()}
        show_monster_section={showMonsterStats}
      />
    );
  };

  return (
    <div className="w-full xl:w-3/4 mx-auto mt-[20px]">
      <Card>
        <div
          className={clsx('grid grid-cols-1 gap-4 p-4 items-start', {
            'lg:grid-cols-[6rem_1fr]': isNotShowingSideSection(),
            'lg:grid-cols-[6rem_1fr_1fr]': isShowingSideSection(),
          })}
        >
          <aside className="flex justify-between lg:flex-col lg:space-y-2 pb-2 lg:pb-0 mx-auto">
            <NavigationActionsComponent />
          </aside>

          {renderActionSection()}

          <ActionSection />
        </div>
      </Card>
    </div>
  );
};

export default Actions;
