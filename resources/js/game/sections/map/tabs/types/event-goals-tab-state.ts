import EventGoal from "../definitions/event-goal";

export default interface EventGoalsTabState {
    loading: boolean;

    eventGoal: EventGoal | null;
}
