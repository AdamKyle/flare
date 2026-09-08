import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import ClassDetail from '../../../game/reusable-components/class/components/class-detail';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { ClassApiMessages } from '../api/enums/class-api-messages';
import { useClassDetail } from '../api/hooks/use-class-detail';
import { ClassScreens } from '../screen-manager/class-screen-constants';
import { useClassScreenNavigation } from '../screen-manager/class-screen-kit';
import { ClassShowScreenProps } from '../screen-manager/class-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ClassShowScreen = ({
  class_id: classId,
}: ClassShowScreenProps): ReactNode => {
  const navigation = useClassScreenNavigation();
  const { game_class: gameClass, loading, error } = useClassDetail(classId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(ClassScreens.FORM, { class_id: classId });
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !gameClass) {
      return (
        <ApiErrorAlert apiError={error?.message ?? ClassApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Class"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <ClassDetail game_class={gameClass} />
      </div>
    );
  };

  return (
    <AdminPage
      title={gameClass?.name ?? 'Class'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default ClassShowScreen;
