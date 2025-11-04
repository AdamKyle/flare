import React from 'react';

import Card from 'ui/cards/card';
import ContainerWrapper from 'ui/container/container-wrapper';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const ManageGuideQuest = () => {
  return (
    <ContainerWrapper>
      <Card>
        <h3>Edit/Create Guide Quests</h3>
        <MarkDownEditor />
      </Card>
    </ContainerWrapper>
  );
};

export default ManageGuideQuest;
