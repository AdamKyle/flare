import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import ItemFormDefinition from '../api/definitions/item-form-definition';
import { ItemApiMessages } from '../api/enums/item-api-messages';
import { useDeleteItem } from '../api/hooks/use-delete-item';
import { useItemDetail } from '../api/hooks/use-item-detail';
import { useItemUsage } from '../api/hooks/use-item-usage';
import AdminItemPresentation from '../components/admin-item-presentation';
import ItemManagementDetails from '../components/item-management-details';
import ItemUsageCard from '../components/item-usage-card';
import { ItemScreens } from '../screen-manager/item-screen-constants';
import { useItemScreenNavigation } from '../screen-manager/item-screen-kit';
import { ItemShowScreenProps } from '../screen-manager/item-screen-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ItemShowScreen = ({
  item_id: itemId,
}: ItemShowScreenProps): ReactNode => {
  const navigation = useItemScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { item, loading, error, refresh } = useItemDetail(itemId);
  const usage = useItemUsage(itemId);
  const { deleting, blockers, delete_item: deleteItem } = useDeleteItem();
  const [announcement, setAnnouncement] = useState('');
  const [confirmingDelete, setConfirmingDelete] = useState(false);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_FORM,
      {
        is_open: true,
        title: 'Edit Item',
        allow_clicking_outside: true,
        item_id: itemId,
        on_saved: (savedItem: ItemFormDefinition) => {
          refresh();
          usage.refresh();
          setAnnouncement(`${savedItem.name} saved.`);
        },
      }
    );
  };

  const handleRequestDelete = (): void => {
    setConfirmingDelete(true);
  };

  const handleOpenQuest = (id: number): void => {
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

  const handleOpenLocation = (id: number): void => {
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
  };

  const handleOpenRelatedEntity = (resource: string, id: number): void => {
    if (resource === 'quest') {
      handleOpenQuest(id);

      return;
    }

    if (resource === 'monster') {
      handleOpenMonster(id);

      return;
    }

    if (resource === 'location') {
      handleOpenLocation(id);
    }

    // `raid` and `guide_quest` have no Phase 2B canonical Admin detail
    // destination and no current Admin/Information route resolves them by
    // id; they render as non-interactive factual text in `ItemUsageCard`
    // instead of a broken or list-redirecting link.
  };

  const handleOpenItem = (relatedItemId: number): void => {
    navigation.navigateTo(ItemScreens.SHOW, { item_id: relatedItemId });
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

  const questItemNavigation = {
    on_open_item: handleOpenItem,
    on_open_location: handleOpenLocation,
    on_open_map: handleOpenMap,
    on_open_npc: handleOpenNpc,
    on_open_quest: handleOpenQuest,
    on_open_monster: handleOpenMonster,
  };

  const handleCancelDelete = (): void => {
    setConfirmingDelete(false);
  };

  const handleConfirmDelete = async (): Promise<void> => {
    const deleted = await deleteItem(itemId);

    if (deleted) {
      navigation.pop();

      return;
    }

    setConfirmingDelete(false);
    usage.refresh();
  };

  const deleteUnavailable =
    usage.loading || !!usage.error || usage.usage?.deletable !== true;

  const renderBlockers = (): ReactNode => {
    if (blockers.length === 0) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.WARNING}>
        <p className="font-medium">This Item cannot be deleted:</p>
        <ul className="list-disc pl-5">
          {blockers.map((blocker) => (
            <li key={blocker}>{blocker}</li>
          ))}
        </ul>
      </Alert>
    );
  };

  const renderDeleteAction = (): ReactNode => {
    if (confirmingDelete) {
      return (
        <div className="flex flex-wrap items-center gap-3">
          <span className="text-glacier-800 dark:text-glacier-200 text-sm">
            Delete this Item permanently? This cannot be undone.
          </span>
          <Button
            label={deleting ? 'Deleting…' : 'Confirm Delete'}
            variant={ButtonVariant.DANGER}
            on_click={handleConfirmDelete}
            disabled={deleting}
          />
          <Button
            label="Cancel"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCancelDelete}
            disabled={deleting}
          />
        </div>
      );
    }

    return (
      <Button
        label="Delete Item"
        variant={ButtonVariant.DANGER}
        on_click={handleRequestDelete}
        disabled={deleteUnavailable}
        aria_label={
          deleteUnavailable
            ? 'Delete Item, unavailable while this Item is still referenced'
            : 'Delete Item'
        }
      />
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !item) {
      return (
        <ApiErrorAlert apiError={error?.message ?? ItemApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Item"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        {renderBlockers()}

        <Card>
          <AdminItemPresentation item={item} navigation={questItemNavigation} />
        </Card>

        <Card>
          <ItemManagementDetails
            type={item.type}
            management={item.management}
          />
        </Card>

        <ItemUsageCard
          usage={usage.usage}
          loading={usage.loading}
          error={usage.error}
          on_open_related_entity={handleOpenRelatedEntity}
        />

        <div className="flex flex-wrap items-center gap-3">
          {renderDeleteAction()}
        </div>
      </div>
    );
  };

  return (
    <AdminPage
      title={item?.name ?? 'Item'}
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

export default ItemShowScreen;
