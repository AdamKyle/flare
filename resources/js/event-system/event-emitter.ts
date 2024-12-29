import EventEmitterDefinition from './deffintions/event-emitter-deffinition';
import EventMapDefinition from './deffintions/event-map-definition';

export default class EventEmitter<T extends EventMapDefinition>
  implements EventEmitterDefinition<T>
{
  private listeners: {
    [K in keyof T]?: Array<(data: T[K]) => void>;
  } = {};

  on<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void {
    if (!this.listeners[eventType]) {
      this.listeners[eventType] = [];
    }

    this.listeners[eventType].push(listener);

    console.log(this.listeners);
  }

  emit<K extends keyof T>(eventType: K, data: T[K]): void {
    const eventListeners = this.listeners[eventType];

    eventListeners?.forEach((listener) => {
      listener(data);
    });
  }

  off<K extends keyof T>(eventType: K, listener: (data: T[K]) => void): void {
    const eventListeners = this.listeners[eventType];

    if (eventListeners) {
      this.listeners[eventType] = eventListeners.filter((l) => l !== listener);
    }
  }
}
