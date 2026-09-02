import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminLocationDetailSidePeekProps from './types/admin-location-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import AdminQuestItemPresentationDefinition from '../../../items/api/definitions/admin-quest-item-presentation-definition';
import { LocationDetailRelatedItemDefinition } from '../../api/definitions/location-detail-definition';
import { LocationApiMessages } from '../../api/enums/location-api-messages';
import { useLocationDetail } from '../../api/hooks/use-location-detail';
import { useLocationQuestItems } from '../../api/hooks/use-location-quest-items';
import LocationFormScreen from '../../screens/location-form-screen';
import LocationDetailBody from '../location-detail-body';
import { LocationNestedSelection } from '../types/location-nested-selection';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

/**
 * Admin Location detail side-peek: stacks the shared, permission-neutral
 * factual Location presentation with an Admin-only Edit action and
 * relationship navigation into other modernized Admin resources. Reuses the
 * exact same `LocationDetailBody` the standalone Location show screen and
 * the Game Map Location side-peek already render, so every entry point
 * shows identical content. Relationship navigation opens the target's
 * canonical detail inside a `StackedCard` over this content (rather than
 * replacing it through the global SidePeek emitter), so this component can
 * itself be reused as nested `StackedCard` content and its own relationship
 * clicks never destroy an ancestor's stack. Edit is composed locally via
 * `StackedCard` and the embeddable `LocationFormScreen`, so opening Edit
 * from a nested context never destroys an ancestor's own stack.
 */
const AdminLocationDetailSidePeek = ({
  location_id: locationId,
  on_location_changed: onLocationChanged,
}: AdminLocationDetailSidePeekProps): ReactNode => {
  const { location, loading, error, refresh } = useLocationDetail(locationId);
  const questItems = useLocationQuestItems(locationId);
  const [showEdit, setShowEdit] = useState(false);
  const [nestedSelection, setNestedSelection] =
    useState<LocationNestedSelection | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const handleEdit = (): void => {
    setShowEdit(true);
  };

  const handleCloseEdit = (): void => {
    setShowEdit(false);
  };

  const handleSaved = (): void => {
    refresh();
    onLocationChanged?.();
    setShowEdit(false);
    setAnnouncement('Location saved.');
  };

  const handleOpenQuestItem = (
    item: AdminQuestItemPresentationDefinition
  ): void => {
    setNestedSelection({
      type: 'item',
      id: item.item_id,
      on_changed: () => questItems.refresh(),
    });
  };

  const handleOpenRelatedItem = (
    item: LocationDetailRelatedItemDefinition
  ): void => {
    setNestedSelection({
      type: 'item',
      id: item.id,
      on_changed: () => refresh(),
    });
  };

  const handleOpenMap = (id: number): void => {
    setNestedSelection({ type: 'map', id });
  };

  const handleCloseNested = (): void => {
    setNestedSelection(null);
  };

  const renderNestedDetail = (): ReactNode => {
    if (!nestedSelection) {
      return null;
    }

    if (nestedSelection.type === 'item') {
      const NestedItemDetail = resolveSidePeekComponent(
        SidePeekComponentRegistrationEnum.ADMIN_ITEM_DETAIL
      );

      return (
        <StackedCard
          on_close={handleCloseNested}
          aria_label="Item Details"
          content_mode={StackedCardContentMode.FULL_BLEED}
        >
          <NestedItemDetail
            is_open
            title="Item Details"
            item_id={nestedSelection.id}
            on_item_changed={nestedSelection.on_changed}
          />
        </StackedCard>
      );
    }

    const NestedGameMapDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_DETAIL
    );

    return (
      <StackedCard
        on_close={handleCloseNested}
        aria_label="Game Map Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedGameMapDetail
          is_open
          title="Game Map Details"
          game_map_id={nestedSelection.id}
        />
      </StackedCard>
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !location) {
      return (
        <ApiErrorAlert apiError={error?.message ?? LocationApiMessages.Load} />
      );
    }

    return (
      <div className="space-y-4">
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

  const renderEdit = (): ReactNode => {
    if (!showEdit || !location) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseEdit} aria_label="Edit Location">
        <LocationFormScreen
          game_map_id={location.game_map.id}
          location_id={locationId}
          initial_x={null}
          initial_y={null}
          on_saved={handleSaved}
          on_cancel={handleCloseEdit}
          embedded
        />
      </StackedCard>
    );
  };

  const isStackActive = showEdit || nestedSelection !== null;

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          isStackActive ? 'overflow-hidden' : 'overflow-y-auto'
        )}
      >
        {renderContent()}
      </div>
      {renderEdit()}
      {renderNestedDetail()}
    </div>
  );
};

export default AdminLocationDetailSidePeek;
