import React, { ReactNode } from 'react';
import { match } from 'ts-pattern';

import CharacterClassRanks from './character-class-ranks';
import CharacterInventoryManagement from './character-inventory-management';
import CharacterReincarnation from './character-reincarnation';
import CharacterSheetDetails from './character-sheet-details';
import { useManageCharacterInventoryVisibility } from './hooks/use-manage-character-inventory-visibility';
import { useManageClassRanksVisibility } from './hooks/use-manage-class-ranks-visibility';
import { useManageReincarnationVisibility } from './hooks/use-manage-reincarnation-visibility';
import { CharacterSheetContainerVisibilityType } from './types/character-sheet-container-visibility-type';
import CharacterSheetProps from './types/character-sheet-props';

import Container from 'ui/container/container';

const CharacterSheet = (props: CharacterSheetProps): ReactNode => {
  const { showReincarnation, openReincarnation, closeReincarnation } =
    useManageReincarnationVisibility();
  const { showClassRanks, openClassRanks, closeClassRanks } =
    useManageClassRanksVisibility();
  const { showInventory, openInventory, closeInventory } =
    useManageCharacterInventoryVisibility();

  const renderCharacterSheetScreen = (): ReactNode => {
    return match({ showReincarnation, showClassRanks, showInventory })
      .with({ showReincarnation: true }, () => <CharacterReincarnation />)
      .with({ showClassRanks: true }, () => <CharacterClassRanks />)
      .with({ showInventory: true }, () => <CharacterInventoryManagement />)
      .otherwise(() => (
        <CharacterSheetDetails
          openReincarnationSystem={openReincarnation}
          openClassRanksSystem={openClassRanks}
          openCharacterInventory={openInventory}
        />
      ));
  };

  const renderTitle = (): string => {
    return match({ showReincarnation, showClassRanks, showInventory })
      .with({ showReincarnation: true }, () => 'Character Name Reincarnation')
      .with({ showClassRanks: true }, () => 'Character Name Class Ranks')
      .with({ showInventory: true }, () => 'Character Name Inventory')
      .otherwise(() => 'Character Name');
  };

  const renderManageSectionVisibility =
    (): CharacterSheetContainerVisibilityType => {
      return match({ showReincarnation, showClassRanks, showInventory })
        .with({ showReincarnation: true }, () => closeReincarnation)
        .with({ showClassRanks: true }, () => closeClassRanks)
        .with({ showInventory: true }, () => closeInventory)
        .otherwise(() => props.manageCharacterSheetVisibility);
    };

  return (
    <Container
      manageSectionVisibility={renderManageSectionVisibility()}
      title={renderTitle()}
    >
      {renderCharacterSheetScreen()}
    </Container>
  );
};

export default CharacterSheet;
