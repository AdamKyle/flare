import { Channel } from "laravel-echo";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import SurveyEventDefinition from "./survey-event-definition";
import SurveyComponent from "../survey-component";

@injectable()
export default class SurveyEvent implements SurveyEventDefinition {
    private component?: SurveyComponent;

    private userId?: number;

    private surveyEvent?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: SurveyComponent, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.surveyEvent = echo.private("show-survey-" + this.userId);
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForSurveyChange();
    }

    /**
     * Listen for the table to update.
     *
     * @protected
     */
    protected listenForSurveyChange() {
        if (!this.surveyEvent) {
            return;
        }

        this.surveyEvent.listen(
            "Game.Survey.Events.ShowSurvey",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const showSurvey = event.showSurvey;

                this.component.setState({
                    show_survey: showSurvey,
                    survey_id: event.surveyId,
                });

                this.component.props.show_survey_button(
                    showSurvey,
                    event.surveyId,
                );
            },
        );
    }
}
