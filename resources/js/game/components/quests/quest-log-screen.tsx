import React, { ReactNode } from 'react';

import QuestLogScreenProps from './types/quest-log-screen-props';
import CharacterQuests from '../character-sheet/character-quests';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const QuestLogScreen = (props: QuestLogScreenProps): ReactNode => {
  return (
    <ContainerWithTitle
      title="Quest Log"
      manageSectionVisibility={props.on_close}
    >
      <Card>
        <div className="min-h-0 p-4">
          <CharacterQuests />
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default QuestLogScreen;
