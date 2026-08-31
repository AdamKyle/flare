import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminItemDetailSidePeekProps from './types/admin-item-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { ItemApiMessages } from '../../api/enums/item-api-messages';
import { useItemDetail } from '../../api/hooks/use-item-detail';
import AdminItemPresentation from '../admin-item-presentation';
import ItemFormContent from '../forms/item-form-content';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminItemDetailSidePeek = ({
  item_id: itemId,
  on_item_changed: onItemChanged,
}: AdminItemDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { item, loading, error, refresh } = useItemDetail(itemId);
  const [showEdit, setShowEdit] = useState(false);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (): void => {
    refresh();
    onItemChanged?.();
    setShowEdit(false);
    setAnnouncement('Item saved.');
  };

  const questItemNavigation = {
    on_open_location: (id: number) => {
      sidePeekEmitter.emit(
        SidePeekEventType.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.ADMIN_LOCATION_DETAIL,
        {
          is_open: true,
          title: 'Location Details',
          allow_clicking_outside: true,
          location_id: id,
        }
      );
    },
    on_open_map: (id: number) => {
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
    },
    on_open_npc: (id: number) => {
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
    },
    on_open_quest: (id: number) => {
      sidePeekEmitter.emit(
        SidePeekEventType.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL,
        {
          is_open: true,
          title: 'Quest Details',
          allow_clicking_outside: true,
          quest_id: id,
        }
      );
    },
    on_open_monster: (id: number) => {
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
    },
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !item) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error?.message ?? ItemApiMessages.Load} />
        </div>
      );
    }

    return (
      <div className="space-y-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit Item"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEdit}
          />
        </div>
        <AdminItemPresentation item={item} navigation={questItemNavigation} />
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit Item">
        <ItemFormContent
          item_id={itemId}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
      {renderEdit()}
    </>
  );
};

export default AdminItemDetailSidePeek;
