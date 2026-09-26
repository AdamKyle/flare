import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseManageSkillsVisibilityDefinition from './definitions/use-manage-skills-visibility-definition';
import { CharacterSheet } from '../event-types/character-sheet';

export const useManageSkillsVisibility =
  (): UseManageSkillsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showSkills, setShowSkills] = useState<boolean>(false);

    const manageSkillsEventEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(CharacterSheet.OPEN_SKILLS_SYSTEM);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowSkills(visible);
      };

      manageSkillsEventEmitter.on(
        CharacterSheet.OPEN_SKILLS_SYSTEM,
        updateVisibility
      );

      return () => {
        manageSkillsEventEmitter.off(
          CharacterSheet.OPEN_SKILLS_SYSTEM,
          updateVisibility
        );
      };
    }, [manageSkillsEventEmitter]);

    const openSkills = () => {
      manageSkillsEventEmitter.emit(CharacterSheet.OPEN_SKILLS_SYSTEM, true);
    };

    const closeSkills = () => {
      manageSkillsEventEmitter.emit(CharacterSheet.OPEN_SKILLS_SYSTEM, false);
    };

    return {
      showSkills,
      openSkills,
      closeSkills,
    };
  };
