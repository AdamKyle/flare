import React from "react";
import Dialogue from "../../ui/dialogue/dialogue";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import MakeCityACapitalModalProps from "../types/modals/make-city-a-capital-modal-props";
import MakeCityACapitalModalState from "../types/modals/make-city-a-capital-modal-state";
import MakeCapitalCityAjax from "../ajax/make-capigtal-city-ajax";
import { serviceContainer } from "../../../../admin/lib/containers/core-container";

export default class MakeCityACapitalModal extends React.Component<
    MakeCityACapitalModalProps,
    MakeCityACapitalModalState
> {
    private capitalCityAjax: MakeCapitalCityAjax;

    constructor(props: MakeCityACapitalModalProps) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };

        this.capitalCityAjax = serviceContainer().fetch(MakeCapitalCityAjax);
    }

    makeCapitalCity() {
        this.setState(
            {
                loading: true,
                error_message: null,
                success_message: null,
            },
            () => {
                this.capitalCityAjax.makeCapitalCity(
                    this,
                    this.props.character_id,
                    this.props.kingdom_id,
                );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Make Capital City"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    handle_action: this.makeCapitalCity.bind(this),
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.success_message !== null,
                    secondary_button_label: "I am sure",
                }}
            >
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}

                <p className="my-2">
                    Are you sure you want to make this kingdom your capital
                    city? You can only have city per plane as your capital city.
                </p>
                <p className="my-2">
                    Capital cities allow you to manage your other kingdoms on
                    the same plane, by issuing orders such as repair, upgrade
                    and recruit units.
                </p>

                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
