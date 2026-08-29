import React, { ReactNode, useState } from 'react';

import AdminItemDetailSidePeekProps from './types/admin-item-detail-side-peek-props';
import { useItemDetail } from '../../api/hooks/use-item-detail';
import { ItemApiMessages } from '../../api/enums/item-api-messages';
import AdminItemPresentation from '../admin-item-presentation';
import ItemFormContent from '../forms/item-form-content';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminItemDetailSidePeek = ({
  item_id: itemId,
  on_item_changed: onItemChanged,
}: AdminItemDetailSidePeekProps): ReactNode => {
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
        <div className="px-4">
          <Button
            label="Edit Item"
            variant={ButtonVariant.PRIMARY}
            on_click={handleEdit}
          />
        </div>
        <AdminItemPresentation item={item} />
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
