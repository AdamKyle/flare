import { container } from "tsyringe";
import GuideQuestListener from "../event-listeners/guide-quest-listener";
import GuideQuestAjax from "../ajax/guide-quest-ajax";
import CompletedGuideQuestListener from "../event-listeners/completed-guide-quest-listener";
var GuideQuestContainer = (function () {
    function GuideQuestContainer() {
        this.register("GuideQuestListenerDefinition", {
            useClass: GuideQuestListener,
        });
        this.register("GuideQuestListenerDefinition", {
            useClass: CompletedGuideQuestListener,
        });
        this.register("guide-quest-ajax", {
            useClass: GuideQuestAjax,
        });
    }
    GuideQuestContainer.getInstance = function () {
        if (!GuideQuestContainer.instance) {
            GuideQuestContainer.instance = new GuideQuestContainer();
        }
        return GuideQuestContainer.instance;
    };
    GuideQuestContainer.prototype.fetch = function (token) {
        return container.resolve(token);
    };
    GuideQuestContainer.prototype.register = function (key, service) {
        container.register(key, { useValue: service });
    };
    return GuideQuestContainer;
})();
var dependencyRegistry;
var guideQuestServiceContainer = function () {
    if (!dependencyRegistry) {
        dependencyRegistry = new GuideQuestContainer();
    }
    return dependencyRegistry;
};
export { guideQuestServiceContainer, GuideQuestContainer };
//# sourceMappingURL=guide-quest-container.js.map
