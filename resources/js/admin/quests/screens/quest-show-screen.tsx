import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import QuestDetail from '../../../game/reusable-components/quest/components/quest-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { QuestApiMessages } from '../api/enums/quest-api-messages';
import { useQuestDetail } from '../api/hooks/use-quest-detail';
import { QuestScreens } from '../screen-manager/quest-screen-constants';
import { useQuestScreenNavigation } from '../screen-manager/quest-screen-kit';
import { QuestShowScreenProps } from '../screen-manager/quest-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestShowScreen = ({
  quest_id: questId,
}: QuestShowScreenProps): ReactNode => {
  const navigation = useQuestScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { quest, loading, error } = useQuestDetail(questId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(QuestScreens.FORM, {
      quest_id: questId,
      parent_quest_id: null,
    });
  };

  const handleAddChild = (): void => {
    navigation.navigateTo(QuestScreens.FORM, {
      quest_id: null,
      parent_quest_id: questId,
    });
  };

  const handleOpenQuest = (id: number): void => {
    navigation.navigateTo(QuestScreens.SHOW, { quest_id: id });
  };

  const handleOpenItem = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: id,
      }
    );
  };

  const handleOpenMonster = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL,
      {
        is_open: true,
        title: 'Monster Details',
        allow_clicking_outside: true,
        monster_id: id,
      }
    );
  };

  const handleOpenNpc = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL,
      {
        is_open: true,
        title: 'NPC Details',
        allow_clicking_outside: true,
        npc_id: id,
      }
    );
  };

  const handleOpenMap = (id: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL,
      {
        is_open: true,
        title: 'Game Map Details',
        allow_clicking_outside: true,
        game_map_id: id,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !quest) {
      return (
        <ApiErrorAlert apiError={error?.message ?? QuestApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start gap-3 py-2">
          <Button
            label="Edit Quest"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
          <Button
            label="Add Child Quest"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleAddChild}
          />
        </div>

        <QuestDetail
          quest={quest}
          navigation={{
            on_open_quest: handleOpenQuest,
            on_open_item: handleOpenItem,
            on_open_monster: handleOpenMonster,
            on_open_npc: handleOpenNpc,
            on_open_map: handleOpenMap,
          }}
        />
      </div>
    );
  };

  return (
    <AdminPage
      title={quest?.name ?? 'Quest'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default QuestShowScreen;
