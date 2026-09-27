import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminUnitDetailSidePeekProps from './types/admin-unit-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { UnitApiMessages } from '../../api/enums/unit-api-messages';
import { useUnitDetail } from '../../api/hooks/use-unit-detail';
import UnitDetail from '../unit-detail';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminUnitDetailSidePeek = ({
  unit_id: unitId,
}: AdminUnitDetailSidePeekProps): ReactNode => {
  const { unit, loading, error } = useUnitDetail(unitId);
  const [nestedBuildingId, setNestedBuildingId] = useState<number | null>(null);

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !unit) {
      return (
        <ApiErrorAlert apiError={error?.message ?? UnitApiMessages.Load} />
      );
    }

    return <UnitDetail unit={unit} on_open_building={setNestedBuildingId} />;
  };

  const renderNestedBuilding = (): ReactNode => {
    if (nestedBuildingId === null) {
      return null;
    }

    const NestedBuildingDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_KINGDOM_BUILDING_DETAIL
    );

    return (
      <StackedCard
        on_close={() => setNestedBuildingId(null)}
        aria_label="Building Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedBuildingDetail
          is_open
          title="Building Details"
          building_id={nestedBuildingId}
        />
      </StackedCard>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          nestedBuildingId === null ? 'overflow-y-auto' : 'overflow-hidden'
        )}
      >
        {renderContent()}
      </div>
      {renderNestedBuilding()}
    </div>
  );
};

export default AdminUnitDetailSidePeek;
