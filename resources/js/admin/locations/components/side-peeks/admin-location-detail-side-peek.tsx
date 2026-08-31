import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import AdminLocationDetailSidePeekProps from './types/admin-location-detail-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import { LocationDetailRelatedItemDefinition } from '../../api/definitions/location-detail-definition';
import { LocationApiMessages } from '../../api/enums/location-api-messages';
import { useLocationDetail } from '../../api/hooks/use-location-detail';
import { useLocationQuestItems } from '../../api/hooks/use-location-quest-items';
import LocationDetailBody from '../location-detail-body';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Location detail side-peek: stacks the shared, permission-neutral
 * factual Location presentation with an Admin-only Edit action and
 * relationship navigation into other modernized Admin resources. Reuses the
 * exact same `LocationDetailBody` the standalone Location show screen and
 * the Game Map Location side-peek already render, so every entry point
 * shows identical content.
 */
const AdminLocationDetailSidePeek = ({
  location_id: locationId,
  on_location_changed: onLocationChanged,
}: AdminLocationDetailSidePeekProps): ReactNode => {
  const sidePeekEmitter = useSidePeekEmitter();
  const { location, loading, error, refresh } = useLocationDetail(locationId);
  const questItems = useLocationQuestItems(locationId);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    if (!location) {
      return;
    }

    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_FORM,
      {
        is_open: true,
        title: 'Edit Location',
        allow_clicking_outside: true,
        game_map_id: location.game_map.id,
        location_id: locationId,
        on_saved: () => {
          refresh();
          onLocationChanged?.();
          setAnnouncement('Location saved.');
        },
      }
    );
  };

  const handleOpenQuestItem = (
    item: AdminQuestItemPresentationDefinition
  ): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: item.item_id,
        on_item_changed: () => questItems.refresh(),
      }
    );
  };

  const handleOpenRelatedItem = (
    item: LocationDetailRelatedItemDefinition
  ): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL,
      {
        is_open: true,
        title: 'Item Details',
        allow_clicking_outside: true,
        item_id: item.id,
        on_item_changed: () => refresh(),
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

    if (error || !location) {
      return (
        <div className="px-4">
          <ApiErrorAlert
            apiError={error?.message ?? LocationApiMessages.Load}
          />
        </div>
      );
    }

    return (
      <div className="space-y-4 px-4">
        <div className="flex justify-center py-2">
          <Button
            label="Edit Location"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <LocationDetailBody
          location={location}
          quest_items={questItems}
          on_open_related_item={handleOpenRelatedItem}
          on_open_quest_item={handleOpenQuestItem}
          on_open_map={handleOpenMap}
        />
      </div>
    );
  };

  return (
    <>
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      {renderContent()}
    </>
  );
};

export default AdminLocationDetailSidePeek;
