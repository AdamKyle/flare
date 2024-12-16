import EventEmitterDeffintion from './deffintions/event-emitter-deffinition';
import EventMapDeffinition from './deffintions/event-map-deffinition';

export default class EventEmitter<T extends EventMapDeffinition>
  implements EventEmitterDeffintion<T>
{
  private listeners: {
    [K in keyof T]?: Array<(data: T[K]) => void>;
  } = {};

  on<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void {
    if (!this.listeners[eventType]) {
      this.listeners[eventType] = [];
    }

    this.listeners[eventType].push(listener);
  }

  emit<K extends keyof T>(eventType: K, data: T[K]): void {
    const eventListeners = this.listeners[eventType];

    eventListeners?.forEach((listener) => {
      listener(data);
    });
  }

  off<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void {
    const eventListeners = this.listeners[eventType];

    eventListeners?.filter((l) => l !== listener);
  }
}
