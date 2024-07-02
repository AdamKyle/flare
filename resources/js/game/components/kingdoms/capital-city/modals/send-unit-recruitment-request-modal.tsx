import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import { serviceContainer } from "../../../../lib/containers/core-container";
import RecruitUnitsSections from "./partials/recruit-units-sections";
import ProcessUnitRequestAjax from "../../ajax/process-unit-request-ajax";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";

interface Unit {
    name: string;
    amount: number;
    kingdom_ids: number[];
}

interface UnitRequest {
    unit_name: string;
    unit_amount: number;
}

interface KingdomUnits {
    kingdom_id: number;
    unit_requests: UnitRequest[];
}

export default class SendUnitRecruitmentRequestModal extends React.Component<
    any,
    any
> {
    private processUnitRecruitmentAjax: ProcessUnitRequestAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };

        this.processUnitRecruitmentAjax = serviceContainer().fetch(
            ProcessUnitRequestAjax,
        );
    }

    sendRequest() {
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                const kingdomUnitsMap: { [key: number]: UnitRequest[] } = {};

                this.props.params.forEach(
                    (unit: { kingdom_ids: any[]; name: any; amount: any }) => {
                        unit.kingdom_ids.forEach((kingdom_id) => {
                            if (!kingdomUnitsMap[kingdom_id]) {
                                kingdomUnitsMap[kingdom_id] = [];
                            }
                            kingdomUnitsMap[kingdom_id].push({
                                unit_name: unit.name,
                                unit_amount: unit.amount,
                            });
                        });
                    },
                );

                const kingdomUnits: KingdomUnits[] = Object.keys(
                    kingdomUnitsMap,
                ).map((key: string) => ({
                    kingdom_id: parseInt(key, 10),
                    unit_requests: kingdomUnitsMap[key as any],
                }));

                this.processUnitRecruitmentAjax.processRequest(
                    this,
                    this.props.character_id,
                    this.props.kingdom_id,
                    kingdomUnits,
                );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Send Unit Recruitment Request"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.success_message !== null,
                    secondary_button_label: "Yes. I understand.",
                    handle_action: this.sendRequest.bind(this),
                }}
            >
                <RecruitUnitsSections />

                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-4"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}

                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
