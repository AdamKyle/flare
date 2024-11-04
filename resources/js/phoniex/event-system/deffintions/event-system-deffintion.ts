import EventEmitterDeffintion from "./event-emitter-deffinition";
import EventMapDeffinition from "./event-map-deffinition";

export default interface EventSystemDeffintion {
    /**
     * Register an event.
     *
     * @param name
     */
    registerEvent<T extends EventMapDeffinition>(
        name: string,
    ): EventEmitterDeffintion<T>;

    /**
     * Get the registered event emitter.
     *
     * @param name
     */
    getEventEmitter<T extends EventMapDeffinition>(
        name: string,
    ): EventEmitterDeffintion<T>;
}
