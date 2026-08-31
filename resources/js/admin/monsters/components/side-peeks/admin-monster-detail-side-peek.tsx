import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminMonsterDetailSidePeekProps from './types/admin-monster-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import MonsterDetail from '../../../../game/reusable-components/monster/components/monster-detail';
import { MonsterApiMessages } from '../../api/enums/monster-api-messages';
import { useMonsterDetail } from '../../api/hooks/use-monster-detail';
import MonsterFormContent from '../forms/monster-form-content';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Monster detail side-peek: stacks the shared, permission-neutral factual Monster
 * presentation with an Admin-only Edit action and relationship navigation into other modernized
 * Admin resources.
 */
const AdminMonsterDetailSidePeek = ({
  monster_id: monsterId,
  on_monster_changed: onMonsterChanged,
}: AdminMonsterDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { monster, loading, error, refresh } = useMonsterDetail(monsterId);
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
    onMonsterChanged?.();
    setShowEdit(false);
    setAnnouncement('Monster saved.');
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

    if (error || !monster) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error?.message ?? MonsterApiMessages.Load} />
        </div>
      );
    }

    return (
      <div className="space-y-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit Monster"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <MonsterDetail
          monster={monster}
          navigation={{
            on_open_item: handleOpenItem,
            on_open_map: handleOpenMap,
          }}
        />
      </div>
    );
  };

  const renderEdit = (): ReactNode => {
    if (!showEdit) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit Monster">
        <MonsterFormContent
          monster_id={monsterId}
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

export default AdminMonsterDetailSidePeek;
