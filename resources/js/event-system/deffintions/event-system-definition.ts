import EventEmitterDeffintion from './event-emitter-deffinition';
import EventMapDeffinition from './event-map-deffinition';

export default interface EventSystemDefinition {
  /**
   * Register an event.
   *
   * @param name
   */
  registerEvent<T extends EventMapDeffinition>(
    name: string
  ): EventEmitterDeffintion<T>;

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
  getEventEmitter<T extends EventMapDeffinition>(
    name: string
  ): EventEmitterDeffintion<T>;
}
