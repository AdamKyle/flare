import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { ReactNode, useState } from 'react';

import AdminPassiveSkillDetailSidePeekProps from './types/admin-passive-skill-detail-side-peek-props';
import { resolveSidePeekComponent } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-mapper';
import { SidePeekComponentRegistrationEnum } from '../../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { PassiveSkillApiMessages } from '../../api/enums/passive-skill-api-messages';
import { usePassiveSkillDetail } from '../../api/hooks/use-passive-skill-detail';
import PassiveSkillDetail from '../passive-skill-detail';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const AdminPassiveSkillDetailSidePeek = ({
  passive_skill_id: passiveSkillId,
}: AdminPassiveSkillDetailSidePeekProps): ReactNode => {
  const {
    passive_skill: passiveSkill,
    loading,
    error,
  } = usePassiveSkillDetail(passiveSkillId);
  const [nestedPassiveSkillId, setNestedPassiveSkillId] = useState<
    number | null
  >(null);

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
      <PassiveSkillDetail
        passive_skill={passiveSkill}
        on_open_passive_skill={setNestedPassiveSkillId}
      />
    );
  };

  const renderNestedDetail = (): ReactNode => {
    if (nestedPassiveSkillId === null) {
      return null;
    }

    const NestedPassiveSkillDetail = resolveSidePeekComponent(
      SidePeekComponentRegistrationEnum.ADMIN_PASSIVE_SKILL_DETAIL
    );

    return (
      <StackedCard
        on_close={() => setNestedPassiveSkillId(null)}
        aria_label="Passive Skill Details"
        content_mode={StackedCardContentMode.FULL_BLEED}
      >
        <NestedPassiveSkillDetail
          is_open
          title="Passive Skill Details"
          passive_skill_id={nestedPassiveSkillId}
        />
      </StackedCard>
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div
        className={clsx(
          'min-h-0 flex-1 px-4 py-4 sm:px-5',
          nestedPassiveSkillId === null ? 'overflow-y-auto' : 'overflow-hidden'
        )}
      >
        {renderContent()}
      </div>
      {renderNestedDetail()}
    </div>
  );
};

export default AdminPassiveSkillDetailSidePeek;
