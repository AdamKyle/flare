import Listener from "../../../../../game/lib/game/event-listeners/listener";
import CharacterTops from "../character-tops";

export default interface CharacterTopsListenerDefinition extends Listener {
    initialize: (
        component: CharacterTops,
        selectedCharacterId: number | null,
    ) => void;
}
