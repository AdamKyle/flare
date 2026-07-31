import { container, InjectionToken } from "tsyringe";
import CharacterTopsAjax from "../ajax/character-tops-ajax";
import CharacterTopsListener from "../event-listeners/character-tops-listener";

class CharacterTopsContainer {
    public constructor() {
        container.register("character-tops-ajax", {
            useClass: CharacterTopsAjax,
        });
        container.register("CharacterTopsListenerDefinition", {
            useClass: CharacterTopsListener,
        });
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }
}

let dependencyRegistry: CharacterTopsContainer;

const characterTopsServiceContainer = (): CharacterTopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new CharacterTopsContainer();
    }

    return dependencyRegistry;
};

export { characterTopsServiceContainer, CharacterTopsContainer };
