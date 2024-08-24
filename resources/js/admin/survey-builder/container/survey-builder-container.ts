import { container, InjectionToken } from "tsyringe";
import CreateNewSurvey from "../ajax/create-new-survey";
import EditSurvey from "../ajax/edit-survey";

class SurveyBuilderContainer {
    private static instance: SurveyBuilderContainer;

    public constructor() {
        this.register("create-new-survey-ajax", {
            useClass: CreateNewSurvey,
        });

        this.register("edit-survey-ajax", {
            useClass: EditSurvey,
        });
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!SurveyBuilderContainer.instance) {
            SurveyBuilderContainer.instance = new SurveyBuilderContainer();
        }
        return SurveyBuilderContainer.instance;
    }

    /**
     * Fetch dependency
     *
     * Throws is the dependency does not exist.
     *
     * @param token
     */
    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }

    /**
     * Register a dependency with the container.
     *
     * @param key
     * @param service
     */
    register<T>(key: string, service: T): void {
        container.register(key, { useValue: service });
    }
}

let dependencyRegistry: SurveyBuilderContainer;

const surveyBuilderContainer = (): SurveyBuilderContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new SurveyBuilderContainer();
    }

    return dependencyRegistry;
};

export { surveyBuilderContainer, SurveyBuilderContainer };
