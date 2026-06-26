import Listener from "../../../../game/lib/game/event-listeners/listener";

export interface GuideQuestCompletedHandler {
    setState(state: { show_guide_quest_completed: boolean }): void;
}

export default interface GuideQuestListenerDefinition extends Listener {
    initialize: (component: GuideQuestCompletedHandler, userId: number) => void;
}
