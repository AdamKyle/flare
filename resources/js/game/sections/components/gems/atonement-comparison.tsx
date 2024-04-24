import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import React, { Fragment } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AddingTheGem from "./adding-the-gem";
import ReplacingAGem from "./replacing-a-gem";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../lib/game/format-number";
import SeerActions from "../../../components/npc-actions/seer-camp/ajax/seer-actions";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import ManageGemsState from "./types/manage-gems-state";
import ManageGemsProps from "./types/manage-gems-props";
import { ActionTypes } from "./types/adding-the-gem-props";
import clsx from "clsx";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "./components/render-atonement-details";
import RenderAtonementAdjustment from "./components/render-atonement-adjustment";
import { Basic } from "react-organizational-chart/dist/stories/Tree.stories";

export default class AtonementComparison extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            is_replacing: false,
            error_message: null,
        };
    }

    closeModals() {
        this.props.manage_modal();
        this.props.manage_parent_modal();
    }

    findAtonementsForReplacing(): any | undefined {
        return this.props.if_replacing.find((data: any) => {
            return data.name_to_replace === this.props.gem_name;
        });
    }

    replaceGem() {
        this.setState(
            {
                is_replacing: true,
                error_message: null,
            },
            () => {
                const gemSocketId = this.findAtonementsForReplacing();

                if (typeof gemSocketId !== "undefined") {
                    SeerActions.replaceGemOnItem(
                        this,
                        this.props.selected_item,
                        this.props.selected_gem,
                        gemSocketId.gem_id,
                    );
                }
            },
        );
    }

    render() {
        const atonementForReplacing = this.findAtonementsForReplacing();

        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Replacement details"}
                primary_button_disabled={this.state.is_replacing}
                secondary_actions={{
                    secondary_button_disabled: this.state.is_replacing,
                    secondary_button_label: "Replace The Gem",
                    handle_action: this.replaceGem.bind(this),
                }}
            >
                <p className="my-4">
                    Below are your Atonement Adjustment Details. Each item can
                    be atoned to a specific element.
                </p>
                <p className="my-4">
                    Upon doing so, taking into account your overall gear and the
                    items atonements, your element damage/resistances could
                    change.
                </p>
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                <div className="my-4 grid lg:grid-cols-2 gap-2">
                    <BasicCard>
                        <h3 className="my-4">
                            With:{" "}
                            <span className="text-lime-600 dark:text-lime-500">
                                {this.props.gem_name}
                            </span>
                        </h3>
                        <RenderAtonementDetails
                            title={"Original Atonement"}
                            original_atonement={this.props.original_atonement}
                        />
                        <div className="my-4">
                            <h4 className="mb-2">
                                Original Elemental Atonement
                            </h4>
                            <dl>
                                <dt>Elemental Atonement</dt>
                                <dd>
                                    {
                                        this.props.original_atonement
                                            .elemental_damage.name
                                    }
                                </dd>
                                <dt>Elemental Damage</dt>
                                <dd>
                                    {(
                                        this.props.original_atonement
                                            .elemental_damage.amount * 100
                                    ).toFixed(2)}
                                    %
                                </dd>
                            </dl>
                        </div>
                    </BasicCard>
                    <BasicCard>
                        {typeof atonementForReplacing !== "undefined" ? (
                            <Fragment>
                                <h3 className="my-4">
                                    When:{" "}
                                    <span className="text-lime-600 dark:text-lime-500">
                                        {atonementForReplacing.name_to_replace}
                                    </span>{" "}
                                    is replaced
                                </h3>
                                <RenderAtonementAdjustment
                                    atonement_for_comparison={
                                        atonementForReplacing.data.atonements
                                    }
                                    original_atonement={
                                        this.props.original_atonement
                                    }
                                />
                                <div className="my-4">
                                    <h4 className="mb-2">
                                        Adjusted Elemental Atonement
                                    </h4>
                                    <dl>
                                        <dt>Elemental Atonement</dt>
                                        <dd>
                                            {
                                                atonementForReplacing.data
                                                    .elemental_damage.name
                                            }
                                        </dd>
                                        <dt>Elemental Damage</dt>
                                        <dd>
                                            {(
                                                atonementForReplacing.data
                                                    .elemental_damage.amount *
                                                100
                                            ).toFixed(2)}
                                            %
                                        </dd>
                                    </dl>
                                </div>
                            </Fragment>
                        ) : (
                            <DangerAlert>
                                Error with finding the atonement to replace.
                            </DangerAlert>
                        )}
                    </BasicCard>
                </div>
                {this.state.is_replacing ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
