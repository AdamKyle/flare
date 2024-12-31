import EventMapDefinition from './event-map-definition';

export default interface EventEmitterDefinition<T extends EventMapDefinition> {
  /**
   * Registers a listener for a specific event type.
   *
   * The listener function will be called when the event is emitted.
   * If the event type expects a single argument, the listener will receive it as `data`.
   * If the event type expects a tuple, the listener will receive each tuple element as separate arguments.
   *
   * @param eventType - The event type to listen for.
   * @param listener - The function to execute when the event is emitted. The listener's signature must match
   *                   the expected argument(s) for the given event type.
   */
  on<K extends keyof T>(
    eventType: K,
    listener: T[K] extends [infer FirstArg, infer SecondArg]
      ? (arg1: FirstArg, arg2: SecondArg) => void
      : T[K] extends [infer FirstArg]
        ? (arg1: FirstArg) => void
        : (data: T[K]) => void
  ): void;

  /**
   * Emits an event of a specific type, passing the required argument(s) to its listeners.
   *
   * If the event type expects a single argument, pass it as the only argument to the emit function.
   * If the event type expects a tuple, pass each tuple element as separate arguments.
   *
   * @param eventType - The event type to emit.
   * @param args - The argument(s) to pass to the listeners. The arguments must match the expected type for the event.
   */
  emit<K extends keyof T>(
    eventType: K,
    ...args: T[K] extends [infer FirstArg, infer SecondArg]
      ? [FirstArg, SecondArg]
      : T[K] extends [infer FirstArg]
        ? [FirstArg]
        : [T[K]]
  ): void;

  /**
   * Removes a previously registered listener for a specific event type.
   *
   * The listener function must exactly match the function that was passed to `on` when registering the event.
   *
   * @param eventType - The event type to stop listening for.
   * @param listener - The function to remove from the event's listener list.
   */
  off<K extends keyof T>(
    eventType: K,
    listener: T[K] extends [infer FirstArg, infer SecondArg]
      ? (arg1: FirstArg, arg2: SecondArg) => void
      : T[K] extends [infer FirstArg]
        ? (arg1: FirstArg) => void
        : (data: T[K]) => void
  ): void;
}
