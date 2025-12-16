import EventEmitterDefinition from './deffintions/event-emitter-deffinition';
import EventMapDefinition from './deffintions/event-map-definition';

export default class EventEmitter<
  T extends EventMapDefinition,
> implements EventEmitterDefinition<T> {
  private listeners: {
    [K in keyof T]?: Array<
      T[K] extends [infer FirstArg, infer SecondArg]
        ? (arg1: FirstArg, arg2: SecondArg) => void
        : T[K] extends [infer FirstArg]
          ? (arg1: FirstArg) => void
          : (data: T[K]) => void
    >;
  } = {};

  on<K extends keyof T>(
    eventType: K,
    listener: T[K] extends [infer FirstArg, infer SecondArg]
      ? (arg1: FirstArg, arg2: SecondArg) => void
      : T[K] extends [infer FirstArg]
        ? (arg1: FirstArg) => void
        : (data: T[K]) => void
  ): void {
    if (!this.listeners[eventType]) {
      this.listeners[eventType] = [];
    }
    this.listeners[eventType]?.push(listener);
  }

  emit<K extends keyof T>(
    eventType: K,
    ...args: T[K] extends [infer FirstArg, infer SecondArg]
      ? [FirstArg, SecondArg]
      : T[K] extends [infer FirstArg]
        ? [FirstArg]
        : [T[K]]
  ): void {
    const eventListeners = this.listeners[eventType];
    if (eventListeners) {
      eventListeners.forEach((listener) => {
        if (args.length === 2) {
          (listener as (arg1: unknown, arg2: unknown) => void)(
            args[0],
            args[1]
          );
        } else if (args.length === 1) {
          (listener as (arg1: unknown) => void)(args[0]);
        } else {
          (listener as (data: unknown) => void)(args[0]);
        }
      });
    }
  }

  off<K extends keyof T>(
    eventType: K,
    listener: T[K] extends [infer FirstArg, infer SecondArg]
      ? (arg1: FirstArg, arg2: SecondArg) => void
      : T[K] extends [infer FirstArg]
        ? (arg1: FirstArg) => void
        : (data: T[K]) => void
  ): void {
    const eventListeners = this.listeners[eventType];
    if (eventListeners) {
      this.listeners[eventType] = eventListeners.filter((l) => l !== listener);
    }
  }
}
