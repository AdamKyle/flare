import EventEmitterDefinition from './event-emitter-deffinition';
import EventMapDefinition from './event-map-definition';

export default interface EventSystemDefinition {
  /**
   * Register an event.
   *
   * @param name
   * @return EventEmitterDefinition<T>
   */
  registerEvent<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T>;

  /**
   * Is the event already registered?
   *
   * @param name
   * @return boolean
   */
  isEventRegistered(name: string): boolean;

  /**
   * Get the registered event emitter.
   *
   * @param name
   * @return EventEmitterDefinition<T>
   */
  getEventEmitter<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T>;

  /**
   * Fetches or creates the event emitter
   *
   * @param name
   * @return EventEmitterDefinition<T>
   */
  fetchOrCreateEventEmitter<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T>;
}
