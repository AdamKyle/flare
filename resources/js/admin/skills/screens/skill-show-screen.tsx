import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { SkillApiMessages } from '../api/enums/skill-api-messages';
import { useSkillDetail } from '../api/hooks/use-skill-detail';
import SkillDetail from '../components/skill-detail';
import { SkillScreens } from '../screen-manager/skill-screen-constants';
import { useSkillScreenNavigation } from '../screen-manager/skill-screen-kit';
import { SkillShowScreenProps } from '../screen-manager/skill-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const SkillShowScreen = ({
  skill_id: skillId,
}: SkillShowScreenProps): ReactNode => {
  const navigation = useSkillScreenNavigation();
  const { skill, loading, error } = useSkillDetail(skillId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(SkillScreens.FORM, { skill_id: skillId });
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !skill) {
      return (
        <ApiErrorAlert apiError={error?.message ?? SkillApiMessages.Load} />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Skill"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <SkillDetail skill={skill} />
      </div>
    );
  };

  return (
    <AdminPage
      title={skill?.name ?? 'Skill'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default SkillShowScreen;
