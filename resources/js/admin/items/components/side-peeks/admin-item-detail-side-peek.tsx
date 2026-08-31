import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminItemDetailSidePeekProps from './types/admin-item-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { ItemApiMessages } from '../../api/enums/item-api-messages';
import { useItemDetail } from '../../api/hooks/use-item-detail';
import AdminItemPresentation from '../admin-item-presentation';
import ItemFormContent from '../forms/item-form-content';
import { ItemNestedSelection } from '../types/item-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Item detail side-peek: stacks the shared, permission-neutral factual quest Item
 * presentation with an Admin-only Edit action and relationship navigation into other
 * modernized Admin resources. Relationship navigation opens the target's canonical detail
 * inside a `StackedCard` over this content (rather than replacing it through the global
 * SidePeek emitter), so this component can itself be reused as nested `StackedCard` content
 * and its own relationship clicks never destroy an ancestor's stack.
 */
const AdminItemDetailSidePeek = ({
  item_id: itemId,
  on_item_changed: onItemChanged,
}: AdminItemDetailSidePeekProps): ReactNode => {
  const { item, loading, error, refresh } = useItemDetail(itemId);
  const [showEdit, setShowEdit] = useState(false);
  const [nestedSelection, setNestedSelection] =
    useState<ItemNestedSelection | null>(null);
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

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const questItemNavigation = {
    on_open_location: (id: number) =>
      setNestedSelection({ type: 'location', id }),
    on_open_map: (id: number) => setNestedSelection({ type: 'map', id }),
    on_open_npc: (id: number) => setNestedSelection({ type: 'npc', id }),
    on_open_quest: (id: number) => setNestedSelection({ type: 'quest', id }),
    on_open_monster: (id: number) =>
      setNestedSelection({ type: 'monster', id }),
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    switch (nestedSelection.type) {
      case 'location': {
        const NestedLocationDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_LOCATION_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Location Details"
          >
            <NestedLocationDetail
              is_open
              title="Location Details"
              location_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'npc': {
        const NestedNpcDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_NPC_DETAIL
        );

        return (
          <StackedCard on_close={handleCloseNested} aria_label="NPC Details">
            <NestedNpcDetail
              is_open
              title="NPC Details"
              npc_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'quest': {
        const NestedQuestDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_QUEST_DETAIL
        );

        return (
          <StackedCard on_close={handleCloseNested} aria_label="Quest Details">
            <NestedQuestDetail
              is_open
              title="Quest Details"
              quest_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      case 'monster': {
        const NestedMonsterDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_MONSTER_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Monster Details"
          >
            <NestedMonsterDetail
              is_open
              title="Monster Details"
              monster_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }

      default: {
        const NestedGameMapDetail = resolveSidePeekComponent(
          SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL
        );

        return (
          <StackedCard
            on_close={handleCloseNested}
            aria_label="Game Map Details"
          >
            <NestedGameMapDetail
              is_open
              title="Game Map Details"
              game_map_id={nestedSelection.id}
            />
          </StackedCard>
        );
      }
    }
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
      {renderNestedDetail()}
    </>
  );
};

export default AdminItemDetailSidePeek;
