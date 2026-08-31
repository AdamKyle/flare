import React, { ReactNode } from 'react';

import QuestFormDefinition from '../api/definitions/quest-form-definition';
import QuestFormContent from '../components/forms/quest-form-content';
import { QuestScreens } from '../screen-manager/quest-screen-constants';
import { useQuestScreenNavigation } from '../screen-manager/quest-screen-kit';
import { QuestFormScreenProps } from '../screen-manager/quest-screen-props';

const QuestFormScreen = ({
  quest_id: questId,
  parent_quest_id: parentQuestId,
}: QuestFormScreenProps): ReactNode => {
  const navigation = useQuestScreenNavigation();

  const handleSaved = (quest: QuestFormDefinition): void => {
    navigation.replaceWith(QuestScreens.SHOW, { quest_id: quest.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <QuestFormContent
      quest_id={questId}
      parent_quest_id={parentQuestId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default QuestFormScreen;
