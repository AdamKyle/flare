import EventEmitterDefinition from './event-emitter-deffinition';
import EventMapDefinition from './event-map-definition';

export default interface EventSystemDefinition {
  /**
   * Register an event.
   *
   * @param name
   */
  registerEvent<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T>;

  /**
   * Is the event already registered?
   *
   * @param name
   */
  isEventRegistered(name: string): boolean;

  /**
   * Get the registered event emitter.
   *
   * @param name
   */
  getEventEmitter<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T>;
}
