import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "./components/render-atonement-details";
import RenderAtonementAdjustment from "./components/render-atonement-adjustment";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SeerActions from "../../../lib/game/actions/seer-camp/seer-actions";

export default class RemoveGemComparison extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            is_removing: false,
            error_message: null,
        }
    }

    findGemNameToRemove(gemId: number): any|null {
        const gem = this.props.gems.find((gem: any) => {
            return gem.gem_id === gemId;
        });

        if (typeof gem !== 'undefined') {
            return gem;
        }

        return null;
    }

    removeGem(gemIdToRemove: number) {
        this.setState({
            is_removing: true,
            error_message: null,
        }, () => {
            SeerActions.removeGem(this, this.props.selected_item, gemIdToRemove)
        })
    }

    removeAllGems() {
        this.setState({
            is_removing: true,
            error_message: null,
        }, () => {
            SeerActions.removeAllGems(this, this.props.selected_item)
        })
    }

    renderOutGemComparison() {
        return this.props.comparison_data.map((comparison: any) => {

            const atonements        = comparison.comparisons.atonements;
            const elementalAtonment = comparison.comparisons.elemental_damage;

            return (
                <BasicCard additionalClasses={'my-4'}>
                    <h3 className='my-4'>When removing: <span className='text-lime-600 dark:text-lime-500'>{this.findGemNameToRemove(comparison.gem_id_to_remove)?.gem_name}</span></h3>
                    <div className='grid lg:grid-cols-2 gap-2'>
                        <div>
                            <RenderAtonementDetails original_atonement={this.props.original_atonement} title={'Original Atonement'} />
                            <div className='my-4'>
                                <h4 className='my-2'>Original Elemental Atonement</h4>
                                <dl>
                                    <dt>Elemental Atonement</dt>
                                    <dd>{this.props.original_atonement.elemental_damage.name}</dd>
                                    <dt>Elemental Damage</dt>
                                    <dd>{(this.props.original_atonement.elemental_damage.amount * 100).toFixed(2)}%</dd>
                                </dl>
                            </div>
                        </div>
                        <div>
                            <RenderAtonementAdjustment atonement_for_comparison={atonements} original_atonement={this.props.original_atonement} />
                            <div className='my-4'>
                                <h4 className='my-2'>Adjusted Elemental Atonement</h4>
                                <dl>
                                    <dt>Elemental Atonement</dt>
                                    <dd>{elementalAtonment.name}</dd>
                                    <dt>Elemental Damage</dt>
                                    <dd>{(elementalAtonment.amount * 100).toFixed(2)}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div className='my-4'>
                        <PrimaryButton button_label={'Remove Gem'} on_click={() => {this.removeGem(comparison.gem_id_to_remove)}} />
                    </div>
                </BasicCard>
            )
        })
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Remove Gem(s) on: ' + this.props.item_name}
                      primary_button_disabled={false}
                      secondary_actions={{
                          secondary_button_label: 'Remove All Gems',
                          secondary_button_disabled: false,
                          handle_action: this.removeAllGems.bind(this),
                      }}
            >
                <InfoAlert>
                    <strong>Removing gems costs 10 Gold bars per gem.</strong>
                </InfoAlert>
                <p className='my-4'>
                    Below is the original atonement of the item as well as the adjusted changes after removing each gem.
                    You may of course choose to remove all gems, at the cost of 10 Gold Bars x The number of gems attached.
                </p>
                <div className='max-h-[350px] overflow-y-scroll mb-4'>
                    {this.renderOutGemComparison()}
                </div>
                {
                    this.state.is_removing ?
                        <LoadingProgressBar />
                    : null
                }
                {
                    this.state.error_message !== null ?
                        <DangerAlert>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
            </Dialogue>
        )
    }
}
