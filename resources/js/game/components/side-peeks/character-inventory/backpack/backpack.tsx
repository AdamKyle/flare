import React, {ReactNode, useState} from 'react';

import BackPackItems from "./back-pack-items";
import QuestItems from "./quest-items";
import BackPackProps from './types/backpack-props';

const BackPack = ({ character_id }: BackPackProps): ReactNode => {
  const [isShowingInventory, setIsShowingInventory] = useState(true);

  if (isShowingInventory) {
    return <BackPackItems character_id={character_id} on_switch_view={setIsShowingInventory} />;
  }

  return <QuestItems character_id={character_id} on_switch_view={setIsShowingInventory} />;
};

export default BackPack;
