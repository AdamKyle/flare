import EventMapDefinition from './event-map-definition';

export default interface EventEmitterDefinition<T extends EventMapDefinition> {
  /**
   * When an event is fired off for a specific type for its listener.
   *
   * @param eventType
   * @param listener
   */
  on<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void;

  /**
   * emit the event with its data.
   *
   * @param eventType
   * @param data
   */
  emit<K extends keyof T>(eventType: K, data: T[K]): void;

  /**
   * Removing an event from the listeners.
   *
   * @param eventType
   * @param listener
   */
  off<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void;
}
