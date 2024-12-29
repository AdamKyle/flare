import React, { ReactNode } from 'react';

import CharacterSheetDetails from './character-sheet-details';
import CharacterSheetProps from './types/character-sheet-props';

import Container from 'ui/container/container';

const CharacterSheet = (props: CharacterSheetProps): ReactNode => {
  return (
    <Container
      manageSectionVisibility={props.manageCharacterSheetVisibility}
      title={'Character Name'}
    >
      <CharacterSheetDetails />
    </Container>
  );
};

export default CharacterSheet;
