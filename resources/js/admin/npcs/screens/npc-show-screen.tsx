import React, { ReactNode, useState } from 'react';

import { NpcApiMessages } from '../api/enums/npc-api-messages';
import { useNpcDetail } from '../api/hooks/use-npc-detail';
import { useNpcQuests } from '../api/hooks/use-npc-quests';
import { useNpcRewardItems } from '../api/hooks/use-npc-reward-items';
import NpcDetailBody from '../components/npc-detail-body';
import { NpcScreens } from '../screen-manager/npc-screen-constants';
import { useNpcScreenNavigation } from '../screen-manager/npc-screen-kit';
import { NpcShowScreenProps } from '../screen-manager/npc-screen-props';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const NpcShowScreen = ({ npc_id: npcId }: NpcShowScreenProps): ReactNode => {
  const navigation = useNpcScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { npc, loading, error, refresh } = useNpcDetail(npcId);
  const quests = useNpcQuests(npcId);
  const rewardItems = useNpcRewardItems(npcId);
  const [announcement, setAnnouncement] = useState('');

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    if (!npc) {
      return;
    }

    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_NPC_FORM,
      {
        is_open: true,
        title: 'Edit NPC',
        allow_clicking_outside: true,
        game_map_id: npc.game_map.id,
        npc_id: npcId,
        on_saved: () => {
          refresh();
          setAnnouncement('NPC saved.');
        },
      }
    );
  };

  const handleOpenItem = (itemId: number, itemName: string): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: itemName,
        allow_clicking_outside: true,
        item_id: itemId,
        on_item_changed: () => {
          quests.refresh();
          rewardItems.refresh();
        },
      }
    );
  };

  const renderDetails = (): ReactNode => {
    if (!npc) {
      return null;
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-end">
          <Button
            label="Edit NPC"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEdit}
          />
        </div>

        <NpcDetailBody
          npc={npc}
          quests={quests}
          reward_items={rewardItems}
          on_open_item={handleOpenItem}
        />
      </div>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !npc) {
      return <ApiErrorAlert apiError={error?.message ?? NpcApiMessages.Load} />;
    }

    return renderDetails();
  };

  return (
    <AdminPage
      title={npc?.real_name ?? 'NPC'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </AdminPage>
  );
};

export default NpcShowScreen;
