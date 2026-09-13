import { SidePeekComponentRegistrationEnum } from '../../../../components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../components/side-peeks/base/hooks/use-side-peek-emitter';

export interface UseOpenManageGemScrollsDefinition {
  openManageGemScrolls: (characterId: number) => void;
}

/**
 * Open the existing Alchemy/Usable Items side-peek pre-filtered to Scrolls,
 * reused as-is so Gem Scroll management never duplicates that inventory
 * experience.
 */
export const useOpenManageGemScrolls =
  (): UseOpenManageGemScrollsDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openManageGemScrolls = (characterId: number): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.USABLE_ITEMS,
        {
          is_open: true,
          title: 'Manage Gem Scrolls',
          character_id: characterId,
          allow_clicking_outside: true,
          initial_filter: 'scrolls',
        }
      );
    };

    return { openManageGemScrolls };
  };
