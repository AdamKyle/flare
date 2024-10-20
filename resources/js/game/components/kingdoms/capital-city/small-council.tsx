import React from "react";
import ClickableIconCard from "../../ui/cards/clickable-icon-card";
import WalkAllKingdomsAjax from "../ajax/walk-all-kingdoms-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import ManageKingdomBuildings from "./manage-kingdom-buildings";
import UnitRecruitment from "./partials/unit-management/unit-recruitment";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import GoldBarManagement from "./gold-bar-management";
import UnitManagement from "./unit-management";

export default class SmallCouncil extends React.Component<any, any> {
    private walkAllKingdomsAjax: WalkAllKingdomsAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            walking_kingdoms: false,
            success_message: null,
            error_message: null,
            show_building_management: false,
            show_unit_recruitment: false,
            show_gold_bars_section: false,
        };

        this.walkAllKingdomsAjax =
            serviceContainer().fetch(WalkAllKingdomsAjax);
    }

    walkKingdoms() {
        if (this.props.kingdom.auto_walked) {
            return;
        }

        this.setState(
            {
                walking_kingdoms: true,
            },
            () => {
                this.walkAllKingdomsAjax.walkKingdoms(
                    this,
                    this.props.kingdom.character_id,
                    this.props.kingdom.id,
                );
            },
        );
    }

    manageShowBuildingManagement() {
        this.setState({
            show_building_management: !this.state.show_building_management,
        });
    }

    manageShowUnitRecruitment() {
        this.setState({
            show_unit_recruitment: !this.state.show_unit_recruitment,
        });
    }

    showGoldBarsAlert() {
        if (this.props.kingdom.small_council_data !== null) {
            return !this.props.kingdom.small_council_data.capital_city_gold_bars
                .can_use;
        }

        return false;
    }

    renderAlertData() {
        const capitalCityGoldBars =
            this.props.kingdom.small_council_data.capital_city_gold_bars;

        return (
            <span>
                You need to complete the quest:{" "}
                <strong>{capitalCityGoldBars.required_quest_name}</strong>{" "}
                first, for The NPC:{" "}
                <strong>{capitalCityGoldBars.for_npc_name}</strong> who lives on
                the plane: <strong>{capitalCityGoldBars.on_plane}</strong>.{" "}
                <span className={"italic"}>
                    (This will be a One Off (Tab) Quest)
                </span>
            </span>
        );
    }

    manageGoldBars() {
        if (this.showGoldBarsAlert()) {
            return;
        }

        this.setState({
            show_gold_bars_section: !this.state.show_gold_bars_section,
        });
    }

    render() {
        if (this.state.show_building_management) {
            return (
                <ManageKingdomBuildings
                    user_id={this.props.user_id}
                    kingdom={this.props.kingdom}
                    manage_building_section={this.manageShowBuildingManagement.bind(
                        this,
                    )}
                />
            );
        }

        if (this.state.show_unit_recruitment) {
            return (
                <UnitManagement
                    user_id={this.props.user_id}
                    kingdom={this.props.kingdom}
                    manage_show_unit_recruitment={this.manageShowUnitRecruitment.bind(
                        this,
                    )}
                />
            );
        }

        if (this.state.show_gold_bars_section) {
            return (
                <GoldBarManagement
                    character_id={this.props.kingdom.character_id}
                    kingdom={this.props.kingdom}
                    manage_gold_bar_management={this.manageGoldBars.bind(this)}
                />
            );
        }

        return (
            <div>
                <h3>Oversee your kingdoms</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p className={"my-2"}>
                    Below you can manage various aspects of all kingdoms that
                    are on the same plane as this one. Because you have stated
                    this is your capital city, you can send out orders to
                    upgrade, repair, recruit and walk kingdoms.
                </p>
                <p className={"my-2 text-blue-500 dark:text-blue-300"}>
                    To begin, read the cards below. Clicking the card can either
                    send off an action or open a new view for you to choose what
                    to do.
                </p>
                <p className="my-4">
                    Learn more{" "}
                    <a href="/information/capital-cities" target="_blank">
                        Capital Cities here{" "}
                        <i className="fas fa-external-link-alt">.</i>
                    </a>
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                <div className="border-2 border-gray-500 dark:border-gray-600 bg-gray-700 dark:bg-gray-600 mr-auto ml-auto p-4 rounded shadow-lg">
                    <ClickableIconCard
                        title={"Walk Kingdoms"}
                        icon_class={"ra ra-cycle"}
                        on_click={this.walkKingdoms.bind(this)}
                    >
                        <p className="mb-2">
                            Clicking this command will automatically send off
                            kingdoms to be walked. This can be accessed once per
                            day. Players only need to walk their kingdoms once
                            every 90 days. If a kingdom has not been walked for
                            90 or more days, the kingdom will be made into an
                            NPC kingdom, up for grabs for 30 days before
                            crumbling.
                        </p>

                        {this.state.walking_kingdoms ? (
                            <LoadingProgressBar />
                        ) : null}

                        {this.props.kingdom.auto_walked ? (
                            <p className="text-red-500 dark:text-red-300">
                                You have already walked your kingdoms today. You
                                can do so again tomorrow.
                            </p>
                        ) : null}
                    </ClickableIconCard>
                    <ClickableIconCard
                        title={"Upgrade/Repair Buildings"}
                        icon_class={"ra ra-heart-tower"}
                        on_click={this.manageShowBuildingManagement.bind(this)}
                    >
                        Clicking this card will allow you to see two lists of
                        buildings: Those that need to be repaired and those that
                        can be upgraded. A building can be upgraded if it does
                        not need to be repaired, is unlocked and is not max
                        level. With that in hand, we have already filtered the
                        buildings you can upgrade across all your kingdoms on
                        this plane.
                    </ClickableIconCard>
                    <ClickableIconCard
                        title={"Recruit Units"}
                        icon_class={"ra ra-crossed-swords"}
                        on_click={this.manageShowUnitRecruitment.bind(this)}
                    >
                        Clicking this card will allow you to recruit units
                        across all your kingdoms. You can specify which kingdoms
                        get what units, or recruit units across all kingdoms. A
                        unit can be recruited if you have not met the max amount
                        of that unit and have the unit unlocked. As a result
                        units you can recruit have been filtered.
                    </ClickableIconCard>
                    <ClickableIconCard
                        title={"Manage Gold Bars"}
                        icon_class={"ri-copper-coin-fill"}
                        on_click={this.manageGoldBars.bind(this)}
                    >
                        {this.showGoldBarsAlert() ? (
                            <WarningAlert additional_css={"mb-4"}>
                                {this.renderAlertData()}
                            </WarningAlert>
                        ) : null}
                        Clicking this card allows you to manage your gold bars
                        across all kingdoms on the same plane as this capital
                        city. When you deposit, we will evenly split the gold
                        bars across the kingdoms. When you withdraw, we will
                        evenly take from every kingdom.
                    </ClickableIconCard>
                </div>
            </div>
        );
    }
}
