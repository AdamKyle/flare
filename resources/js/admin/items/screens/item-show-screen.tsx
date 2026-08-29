import React, { ReactNode, useState } from 'react';

import { ItemApiMessages } from '../api/enums/item-api-messages';
import { useDeleteItem } from '../api/hooks/use-delete-item';
import { useItemDetail } from '../api/hooks/use-item-detail';
import AdminItemPresentation from '../components/admin-item-presentation';
import ItemFormDefinition from '../api/definitions/item-form-definition';
import { ITEM_ALCHEMY_TYPE_LABELS } from '../enums/item-alchemy-type';
import { ITEM_CATALOG_TYPE_LABELS } from '../enums/item-catalog-type';
import { ITEM_CRAFTING_TYPE_LABELS } from '../enums/item-crafting-type';
import { ITEM_DEFAULT_POSITION_LABELS } from '../enums/item-default-position';
import { ITEM_SPECIALTY_TYPE_LABELS } from '../enums/item-specialty-type';
import { ItemScreens } from '../screen-manager/item-screen-constants';
import { useItemScreenNavigation } from '../screen-manager/item-screen-kit';
import { ItemShowScreenProps } from '../screen-manager/item-screen-props';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ItemShowScreen = ({
  item_id: itemId,
}: ItemShowScreenProps): ReactNode => {
  const navigation = useItemScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { item, loading, error, refresh } = useItemDetail(itemId);
  const { deleting, blockers, delete_item: deleteItem } = useDeleteItem();
  const [announcement, setAnnouncement] = useState('');

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
          setAnnouncement(`${savedItem.name} saved.`);
        },
      }
    );
  };

  const handleDelete = async (): Promise<void> => {
    const deleted = await deleteItem(itemId);

    if (deleted) {
      navigation.pop();
    }
  };

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
        <div className="flex flex-wrap justify-end gap-3">
          <Button
            label="Edit Item"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEdit}
          />
          <Button
            label={deleting ? 'Deleting…' : 'Delete Item'}
            variant={ButtonVariant.DANGER}
            on_click={handleDelete}
            disabled={deleting}
          />
        </div>

        {renderBlockers()}

        <AdminItemPresentation item={item} />

        <section className="px-4">
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
            Catalog Management
          </h2>
          <Dl>
            <Dt>Type</Dt>
            <Dd>{ITEM_CATALOG_TYPE_LABELS[item.type]}</Dd>
            <Dt>Craftable</Dt>
            <Dd>{item.management.can_craft ? 'Yes' : 'No'}</Dd>
            <Dt>Crafting Type</Dt>
            <Dd>
              {item.management.crafting_type === null
                ? 'None'
                : ITEM_CRAFTING_TYPE_LABELS[item.management.crafting_type]}
            </Dd>
            <Dt>Market Sellable</Dt>
            <Dd>{item.management.market_sellable ? 'Yes' : 'No'}</Dd>
            <Dt>Can Drop</Dt>
            <Dd>{item.management.can_drop ? 'Yes' : 'No'}</Dd>
            <Dt>Default Position</Dt>
            <Dd>
              {item.management.default_position === null
                ? 'None'
                : ITEM_DEFAULT_POSITION_LABELS[
                    item.management.default_position
                  ]}
            </Dd>
            <Dt>Specialty Type</Dt>
            <Dd>
              {item.management.specialty_type === null
                ? 'None'
                : ITEM_SPECIALTY_TYPE_LABELS[item.management.specialty_type]}
            </Dd>
            <Dt>Alchemy Type</Dt>
            <Dd>
              {item.management.alchemy_type === null
                ? 'None'
                : ITEM_ALCHEMY_TYPE_LABELS[item.management.alchemy_type]}
            </Dd>
            <Dt>Unlocks Class</Dt>
            <Dd>{item.management.unlocks_class?.name ?? 'None'}</Dd>
            <Dt>Item Skill</Dt>
            <Dd>{item.management.item_skill?.name ?? 'None'}</Dd>
          </Dl>
        </section>
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
