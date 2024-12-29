import React, { ReactNode } from 'react';

import CharacterSheetProps from './types/character-sheet-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Container from 'ui/container/container';

const CharacterSheet = (props: CharacterSheetProps): ReactNode => {
  return (
    <Container>
      <div className="flex justify-end mb-4">
        <Button
          on_click={props.manageCharacterSheetVisibility}
          label="Close"
          variant={ButtonVariant.DANGER}
        />
      </div>
      Character Sheet Jazz here ...
    </Container>
  );
};

export default CharacterSheet;
