import React, { ReactNode, useState } from 'react';

import BackpackItems from './backpack-items';
import QuestItems from './quest-items';
import BackpackProps from './types/backpack-props';

import { useGameData } from 'game-data/hooks/use-game-data';

const BackPack = ({
  initial_search_text,
  initial_filters,
  target_position,
  on_equipment_changed,
}: BackpackProps): ReactNode => {
  const { gameData, updateCharacter } = useGameData();

  const [isShowingInventory, setIsShowingInventory] = useState(true);

  if (!gameData?.character) {
    return null;
  }

  if (isShowingInventory) {
    return (
      <BackpackItems
        character={gameData.character}
        update_character={updateCharacter}
        on_switch_view={setIsShowingInventory}
        initial_search_text={initial_search_text}
        initial_filters={initial_filters}
        target_position={target_position}
        on_equipment_changed={on_equipment_changed}
      />
    );
  }

  return (
    <QuestItems
      character={gameData.character}
      on_switch_view={setIsShowingInventory}
    />
  );
};

export default BackPack;
