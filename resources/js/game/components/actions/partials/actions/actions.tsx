import clsx from 'clsx';
import React, { ReactNode } from 'react';

import ActionSection from './action-section';
import NavigationActionsComponent from './navigation-actions';
import AttackMessages from '../../components/fight-section/attack-messages';
import MonsterSection from '../monster-section/monster-section';
import { useIsMobile } from './hooks/use-is-mobile';
import ActionsProps from './types/actions-props';
import AttackMessageDefinition from '../../components/fight-section/deffinitions/attack-message-definition';
import { AttackMessageType } from '../../components/fight-section/enums/attack-message-type';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../floating-cards/map-section/hooks/use-manage-map-section-visibility';

import Card from 'ui/cards/card';

const Actions = (props: ActionsProps): ReactNode => {
  const { isMobile } = useIsMobile();
  const { showMonsterStats } = props;

  const { showCharacterCard } = useManageCharacterCardVisibility();

  const { showCraftingCard } = useManageCraftingCardVisibility();

  const { showMapCard } = useManageMapSectionVisibility();

  const isShowingSideSection = (): boolean => {
    return showCharacterCard || showCraftingCard || showMapCard;
  };

  const isNotShowingSideSection = (): boolean => {
    return !showCraftingCard && !showCharacterCard && !showMapCard;
  };

  const messages: AttackMessageDefinition[] = [
    {
      message: 'You Attack for 150,000 Damage!',
      type: AttackMessageType.PLAYER_ATTACK,
    },
    {
      message: 'Your spells charge and magic crackles in the air!',
      type: AttackMessageType.REGULAR,
    },
    {
      message: 'The enemy stops your spells and attack you for 125,000 Damage',
      type: AttackMessageType.ENEMY_ATTACK,
    },
  ];

  const renderActionSection = () => {
    if (isMobile && isShowingSideSection()) {
      return null;
    }

    return (
      <div className="flex flex-col items-center space-y-4">
        <div
          className={clsx('w-full lg:w-1/2 mx-auto', {
            'lg:w-full': isShowingSideSection(),
          })}
        >
          <MonsterSection show_monster_stats={showMonsterStats} />
          <div className="mt-4 rounded-lg bg-gray-100 dark:bg-gray-700 p-4 text-sm border border-solid border-gray-200 dark:border-gray-800 ">
            <AttackMessages messages={messages} />
          </div>
        </div>
      </div>
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
