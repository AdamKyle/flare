import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { PassiveSkillApiMessages } from '../api/enums/passive-skill-api-messages';
import { usePassiveSkillDetail } from '../api/hooks/use-passive-skill-detail';
import PassiveSkillDetail from '../components/passive-skill-detail';
import { PassiveSkillScreens } from '../screen-manager/passive-skill-screen-constants';
import { usePassiveSkillScreenNavigation } from '../screen-manager/passive-skill-screen-kit';
import { PassiveSkillShowScreenProps } from '../screen-manager/passive-skill-screen-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const PassiveSkillShowScreen = ({
  passive_skill_id: passiveSkillId,
}: PassiveSkillShowScreenProps): ReactNode => {
  const navigation = usePassiveSkillScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const {
    passive_skill: passiveSkill,
    loading,
    error,
  } = usePassiveSkillDetail(passiveSkillId);

  const handleBack = (): void => {
    navigation.pop();
  };

  const handleEdit = (): void => {
    navigation.navigateTo(PassiveSkillScreens.FORM, {
      passive_skill_id: passiveSkillId,
    });
  };

  const handleOpenPassiveSkill = (relatedPassiveSkillId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_PASSIVE_SKILL_DETAIL,
      {
        is_open: true,
        title: 'Passive Skill Details',
        allow_clicking_outside: true,
        passive_skill_id: relatedPassiveSkillId,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error || !passiveSkill) {
      return (
        <ApiErrorAlert
          apiError={error?.message ?? PassiveSkillApiMessages.Load}
        />
      );
    }

    return (
      <div className="flex flex-col gap-6">
        <div className="flex justify-start py-2">
          <Button
            label="Edit Passive Skill"
            variant={ButtonVariant.PRIMARY}
            additional_css="text-sm px-3 py-1.5"
            on_click={handleEdit}
          />
        </div>
        <PassiveSkillDetail
          passive_skill={passiveSkill}
          on_open_passive_skill={handleOpenPassiveSkill}
        />
      </div>
    );
  };

  return (
    <AdminPage
      title={passiveSkill?.name ?? 'Passive Skill'}
      width={AdminPageWidth.Detail}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      {renderContent()}
    </AdminPage>
  );
};

export default PassiveSkillShowScreen;
