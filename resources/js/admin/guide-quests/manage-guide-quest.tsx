import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';
import React from 'react';
import { ServiceContainer } from 'service-container-provider/service-container';

import ManageGuideQuestsForm from './form-components/manage-guide-quests-form';
import ManageGuideQuestProps from './types/manage-guide-quest-props';

const ManageGuideQuest = ({ guide_quest_id }: ManageGuideQuestProps) => {
  return (
    <ServiceContainer>
      <ApiHandlerProvider>
        <ManageGuideQuestsForm guide_quest_id={guide_quest_id} />
      </ApiHandlerProvider>
    </ServiceContainer>
  );
};

export default ManageGuideQuest;
