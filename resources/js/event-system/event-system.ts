import { singleton } from 'tsyringe';

import EventEmitterDefinition from './deffintions/event-emitter-deffinition';
import EventMapDefinition from './deffintions/event-map-definition';
import EventSystemDefinition from './deffintions/event-system-definition';
import EventEmitter from './event-emitter';

@singleton()
export default class EventSystem implements EventSystemDefinition {
  private emitters: {
    [key: string]: EventEmitterDefinition<EventMapDefinition>;
  } = {};

  isEventRegistered(name: string): boolean {
    return !!this.emitters[name];
  }

  registerEvent<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T> {
    if (this.emitters[name]) {
      throw new Error(`Emitter name: ${name} is already registered.`);
    }

    const emitter = new EventEmitter<T>() as EventEmitterDefinition<T>;
    this.emitters[name] = emitter;

    return emitter;
  }

  getEventEmitter<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T> {
    const emitter = this.emitters[name];

    if (!emitter) {
      throw new Error(`${name} is not registered.`);
    }

    return emitter as EventEmitterDefinition<T>;
  }

  fetchOrCreateEventEmitter<T extends EventMapDefinition>(
    name: string
  ): EventEmitterDefinition<T> {
    if (this.isEventRegistered(name)) {
      return this.getEventEmitter(name);
    }

    return this.registerEvent(name);
  }
}
