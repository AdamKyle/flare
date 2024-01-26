import React from "react";
import BuildingDetails from "./deffinitions/building-details";
import ResourceBuildingExpansionProps from "./types/resource-building-expansion-props";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import ResourceBuildingExpansionState from "./types/resource-building-expansion-state";
import clsx from "clsx";

export default class ResourceBuildingExpansion extends React.Component<ResourceBuildingExpansionProps, ResourceBuildingExpansionState> {

    constructor(props: ResourceBuildingExpansionProps) {
        super(props);

        this.state = {
            expanding: false,
            error_message: null,
            success_message: null,
            time_remaining_for_expansion: 0,
        }
    }

    canNotExpand(building: BuildingDetails): boolean {
        if (!building.is_maxed) {
            return true;
        }

        if (this.props.building_needs_to_be_repaired) {
            return true;
        }

        return this.state.expanding;
    }

    expandBuilding() {
        this.setState({
            error_message: null,
            success_message: null,
            expanding: true,
        }, () => {

        })
    }

    render() {
        return (
            <>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <h3>Expand</h3>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <p className='my-2'>
                    Once a building reaches it max level and does not need to be repaired, one can expand its production to produce
                    more resources allowing you to recruit more units.
                </p>
                <p className='my-2'>
                    <a href='/information/kingdom-resource-expansion' target='_blank'>Learn more about expanding resource buildings <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
                <dl className='my-4'>
                    <dt>Resource slot count</dt>
                    <dd>0</dd>
                    <dt>Resource slots left</dt>
                    <dd>8</dd>
                    <dt>Cost to expand</dt>
                    <dd>250 Gold Bars</dd>
                </dl>
                <DangerAlert additional_css={clsx({
                    'hidden': this.state.error_message === null,
                    'my-4': this.state.error_message !== null
                })}>
                    {this.state.error_message}
                </DangerAlert>
                <SuccessAlert additional_css={clsx({
                    'hidden': this.state.success_message === null,
                    'my-4': this.state.error_message !== null
                })}>
                    {this.state.success_message}
                </SuccessAlert>
                {
                    this.state.expanding ?
                        <LoadingProgressBar />
                    : null
                }
                <PrimaryOutlineButton button_label={'Expand Production'} on_click={this.expandBuilding.bind(this)} disabled={this.canNotExpand(this.props.building)} additional_css={'my-2'} />
                <TimerProgressBar time_remaining={this.state.time_remaining_for_expansion} time_out_label={'Expanding'} />
            </>
        )
    }
}
