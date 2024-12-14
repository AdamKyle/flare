import { singleton } from "tsyringe";

import EventEmitterDeffintion from "./deffintions/event-emitter-deffinition";
import EventMapDeffinition from "./deffintions/event-map-deffinition";
import EventSystemDefinition from "./deffintions/event-system-definition";
import EventEmitter from "./event-emitter";

@singleton()
export default class EventSystem implements EventSystemDefinition {
    private emitters: {
        [key: string]: EventEmitterDeffintion<EventMapDeffinition>;
    } = {};

    isEventRegistered(name: string): boolean {
        return !!this.emitters[name];
    }

    registerEvent<T extends EventMapDeffinition>(
        name: string,
    ): EventEmitterDeffintion<T> {
        if (this.emitters[name]) {
            throw new Error(`Emitter name: ${name} is already registered.`);
        }

        const emitter = new EventEmitter<T>() as EventEmitterDeffintion<T>;
        this.emitters[name] = emitter;

        return emitter;
    }
    getEventEmitter<T extends EventMapDeffinition>(
        name: string,
    ): EventEmitterDeffintion<T> {
        const emitter = this.emitters[name];

        if (!emitter) {
            throw new Error(`${name} is not registered.`);
        }

        return emitter as EventEmitterDeffintion<T>;
    }
}
