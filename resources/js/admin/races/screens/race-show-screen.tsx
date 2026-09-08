import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import RaceDetail from '../../../game/reusable-components/race/components/race-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { RaceApiMessages } from '../api/enums/race-api-messages';
import { useRaceDetail } from '../api/hooks/use-race-detail';
import { RaceScreens } from '../screen-manager/race-screen-constants';
import { useRaceScreenNavigation } from '../screen-manager/race-screen-kit';
import { RaceShowScreenProps } from '../screen-manager/race-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const RaceShowScreen = ({
  race_id: raceId,
}: RaceShowScreenProps): ReactNode => {
  const navigation = useRaceScreenNavigation();
  const { race, loading, error } = useRaceDetail(raceId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(RaceScreens.FORM, { race_id: raceId });
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !race) {
      return (
        <ApiErrorAlert apiError={error?.message ?? RaceApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Race"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>

        <RaceDetail race={race} />
      </div>
    );
  };

  return (
    <AdminPage
      title={race?.name ?? 'Race'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default RaceShowScreen;
