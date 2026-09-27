import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { UnitApiMessages } from '../api/enums/unit-api-messages';
import { useUnitDetail } from '../api/hooks/use-unit-detail';
import UnitDetail from '../components/unit-detail';
import { UnitScreens } from '../screen-manager/unit-screen-constants';
import { useUnitScreenNavigation } from '../screen-manager/unit-screen-kit';
import { UnitShowScreenProps } from '../screen-manager/unit-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const UnitShowScreen = ({
  unit_id: unitId,
}: UnitShowScreenProps): ReactNode => {
  const navigation = useUnitScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { unit, loading, error } = useUnitDetail(unitId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(UnitScreens.UNIT_FORM, { unit_id: unitId });
  };

  const handleOpenBuilding = (buildingId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_KINGDOM_BUILDING_DETAIL,
      {
        is_open: true,
        title: 'Building Details',
        allow_clicking_outside: true,
        building_id: buildingId,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !unit) {
      return (
        <ApiErrorAlert apiError={error?.message ?? UnitApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Unit"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <UnitDetail unit={unit} on_open_building={handleOpenBuilding} />
      </div>
    );
  };

  return (
    <AdminPage
      title={unit?.name ?? 'Unit'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default UnitShowScreen;
