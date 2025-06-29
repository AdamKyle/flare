import React from 'react';

import PlayerKingdomsProps from './types/player-kingdoms-props';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const PlayerKingdoms = ({ close_shop }: PlayerKingdomsProps) => {
  return (
    <ContainerWithTitle
      manageSectionVisibility={close_shop}
      title={`Your kingdoms`}
    >
      <Card>
        <p className={'my-4 italic text-gray-800 dark:text-gray-300'}>
          Show character kingdoms
        </p>
      </Card>
    </ContainerWithTitle>
  );
};

export default PlayerKingdoms;
