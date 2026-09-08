import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import ClassMasteryDetail from '../../../game/reusable-components/class-mastery/components/class-mastery-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { ClassMasteryApiMessages } from '../api/enums/class-mastery-api-messages';
import { useClassMasteryDetail } from '../api/hooks/use-class-mastery-detail';
import { ClassMasteryScreens } from '../screen-manager/class-mastery-screen-constants';
import { useClassMasteryScreenNavigation } from '../screen-manager/class-mastery-screen-kit';
import { ClassMasteryShowScreenProps } from '../screen-manager/class-mastery-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ClassMasteryShowScreen = ({
  class_mastery_id: classMasteryId,
}: ClassMasteryShowScreenProps): ReactNode => {
  const navigation = useClassMasteryScreenNavigation();
  const {
    class_mastery: classMastery,
    loading,
    error,
  } = useClassMasteryDetail(classMasteryId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(ClassMasteryScreens.FORM, {
      class_mastery_id: classMasteryId,
    });
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !classMastery) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? ClassMasteryApiMessages.Load}
        />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Class Mastery"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <ClassMasteryDetail class_mastery={classMastery} />
      </div>
    );
  };

  return (
    <AdminPage
      title={classMastery?.name ?? 'Class Mastery'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default ClassMasteryShowScreen;
