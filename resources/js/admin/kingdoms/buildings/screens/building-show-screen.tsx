import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../../shared/components/admin-back-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import { BuildingApiMessages } from '../api/enums/building-api-messages';
import { useBuildingDetail } from '../api/hooks/use-building-detail';
import BuildingDetail from '../components/building-detail';
import { BuildingScreens } from '../screen-manager/building-screen-constants';
import { useBuildingScreenNavigation } from '../screen-manager/building-screen-kit';
import { BuildingShowScreenProps } from '../screen-manager/building-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const BuildingShowScreen = ({
  building_id: buildingId,
}: BuildingShowScreenProps): ReactNode => {
  const navigation = useBuildingScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const { building, loading, error } = useBuildingDetail(buildingId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(BuildingScreens.BUILDING_FORM, {
      building_id: buildingId,
    });
  };

  const handleOpenUnit = (unitId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_KINGDOM_UNIT_DETAIL,
      {
        is_open: true,
        title: 'Unit Details',
        allow_clicking_outside: true,
        unit_id: unitId,
      }
    );
  };

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
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Building"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <BuildingDetail building={building} on_open_unit={handleOpenUnit} />
      </div>
    );
  };

  return (
    <AdminPage
      title={building?.name ?? 'Building'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default BuildingShowScreen;
