import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminBuildingDetailSidePeekProps from './types/admin-building-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { BuildingApiMessages } from '../../api/enums/building-api-messages';
import { useBuildingDetail } from '../../api/hooks/use-building-detail';
import BuildingDetail from '../building-detail';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminBuildingDetailSidePeek = ({
  building_id: buildingId,
}: AdminBuildingDetailSidePeekProps): ReactNode => {
  const { building, loading, error } = useBuildingDetail(buildingId);
  const [nestedUnitId, setNestedUnitId] = useState<number | null>(null);

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !building) {
      return (
        <ApiErrorAlert apiError={error?.message ?? BuildingApiMessages.Load} />
      );
    }

    return (
      <BuildingDetail building={building} on_open_unit={setNestedUnitId} />
    );
  };

  const renderNestedUnit = (): ReactNode => {
    if (nestedUnitId === null) {
      return null;
    }

    const NestedUnitDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_KINGDOM_UNIT_DETAIL
    );

    return (
      <StackedCard
        on_close={() => setNestedUnitId(null)}
        aria_label="Unit Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedUnitDetail is_open title="Unit Details" unit_id={nestedUnitId} />
      </StackedCard>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          nestedUnitId === null ? 'overflow-y-auto' : 'overflow-hidden'
        )}
      >
        {renderContent()}
      </div>
      {renderNestedUnit()}
    </div>
  );
};

export default AdminBuildingDetailSidePeek;
