import React from 'react';

import ManageGuideQuestSectionContent from './components/manage-guide-quest-section-content';

import Card from 'ui/cards/card';
import WideContainerWrapper from 'ui/container/wide-container-wrapper';
import Separator from 'ui/separator/separator';

const ManageGuideQuest = () => {
  return (
    <WideContainerWrapper>
      <Card>
        <h2 className={'text-xl text-gray-800 dark:text-gray-400'}>
          Edit/Create Guide Quests
        </h2>
        <Separator />
        <ManageGuideQuestSectionContent />
      </Card>
    </WideContainerWrapper>
  );
};

export default ManageGuideQuest;
