import { upperFirst } from "lodash";
import React, { Fragment } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerButton from "../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../../lib/containers/core-container";
import { formatNumber } from "../../../lib/game/format-number";
import UpgradeWithResourcesAjax from "../ajax/upgrade-with-resources-ajax";

export default class UpgradeWithResources extends React.Component<any, any> {
    private upgradeBuildingAjax: UpgradeWithResourcesAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            loading: false,
        };

        this.upgradeBuildingAjax = serviceContainer().fetch(
            UpgradeWithResourcesAjax,
        );
    }

    upgradeBuilding() {
        this.setState(
            {
                error_message: null,
                success_message: null,
                loading: true,
            },
            () => {
                this.upgradeBuildingAjax.upgradeBuilding(
                    this,
                    this.props.character_id,
                    this.props.building.id,
                    1,
                    false,
                );
            },
        );
    }

    renderFutureResourceValues() {
        const resourceValues = [
            "future_clay_increase",
            "future_defence_increase",
            "future_durability_increase",
            "future_iron_increase",
            "future_population_increase",
            "future_stone_increase",
            "future_wood_increase",
        ];

        return resourceValues
            .map((value: string) => {
                const buildingValue = this.props.building[value];

                if (
                    buildingValue !== null &&
                    typeof buildingValue === "number"
                ) {
                    if (buildingValue > 0.0) {
                        return (
                            <Fragment>
                                <dt>
                                    {upperFirst(
                                        value
                                            .replace("future_", "")
                                            .replace("_", " "),
                                    )}
                                </dt>
                                <dd>{formatNumber(buildingValue)}</dd>
                            </Fragment>
                        );
                    }
                }
            })
            .filter((element: any) => {
                return typeof element !== "undefined";
            });
    }

    render() {
        return (
            <Fragment>
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"mb-5"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"mb-5"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                <h3>After Level Up</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                <dl className="mb-5">{this.renderFutureResourceValues()}</dl>
                {this.state.loading ? <LoadingProgressBar /> : null}
                <PrimaryButton
                    button_label={"Upgrade"}
                    additional_css={"mr-2"}
                    on_click={this.upgradeBuilding.bind(this)}
                    disabled={this.state.loading || this.props.is_in_queue}
                />
                <DangerButton
                    button_label={"Cancel"}
                    on_click={this.props.remove_section.bind(this)}
                    disabled={this.state.loading}
                />
            </Fragment>
        );
    }
}
