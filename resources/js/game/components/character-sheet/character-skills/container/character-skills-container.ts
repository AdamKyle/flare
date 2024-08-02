import { CoreContainer } from "../../../../lib/containers/core-container";
import KingdomPassivesAjax from "../ajax/kingdom-passives-ajax";
import CharacterSkillsAjax from "../ajax/character-skills-ajax";
import KingdomPassiveSkillsEvent from "../event-listeners/kingdom-passive-skills-event";

/**
 * Registers the various classes including ajax and event listeners.
 *
 * @param container
 */
function characterSkillsContainer(container: CoreContainer) {
    container.register("kingdom-passive-ajax", {
        useClass: KingdomPassivesAjax,
    });

    container.register("character-skills-ajax", {
        useClass: CharacterSkillsAjax,
    });

    container.register("kingdom-passive-skills-event-definition", {
        useClass: KingdomPassiveSkillsEvent,
    });
}

export default characterSkillsContainer;
