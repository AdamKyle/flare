import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import GemComparisonDetails from "./gem-comparison-details";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import clsx from "clsx";
import ReplacingAGemProps from "./types/replacing-a-gem-props";
import AttachedGems from "./deffinitions/attached-gems";
import WhenReplacing from "./deffinitions/when-replacing";
import AtonementComparison from "./atonement-comparison";

export default class ReplacingAGem<T> extends React.Component<ReplacingAGemProps<T>, any> {
    constructor(props: ReplacingAGemProps<T>) {
        super(props);

        this.state = {
            show_replacement_comparison: false,
            gem_name_to_replace: null,
        }
    }

    replaceGem(gemName: string) {
        this.setState({
            show_replacement_comparison: true,
            gem_name_to_replace: gemName,
        });
    }

    displayCards(): JSX.Element[] {
        return this.props.when_replacing.map((gemComparisonDetails: WhenReplacing) => {
            const gemYouHave = this.props.gems_you_have.filter((gem: AttachedGems) => {
                return gem.id === gemComparisonDetails.gem_you_have_id;
            })[0];

            return (
                <BasicCard additionalClasses='my-4'>
                    <div className='grid md:grid-cols-2 gap-2'>
                        <div>
                            <h3 className='my-4 text-lime-600 dark:text-lime-500'>{gemComparisonDetails.name} (When Replacing)</h3>
                            <GemComparisonDetails gem={gemComparisonDetails}/>
                        </div>
                        <div>
                            <h3 className='my-4 text-lime-600 dark:text-lime-500'>{gemYouHave.name} (Currently Socketed)</h3>
                            <Fragment>
                                <dl>
                                    <dt>Tier</dt>
                                    <dd>{gemYouHave.tier}</dd>
                                    <dt>{gemYouHave.primary_atonement_name + ' Atonement: '}</dt>
                                    <dd>{(gemYouHave.primary_atonement_amount * 100).toFixed(0)}%</dd>
                                    <dt>{gemYouHave.secondary_atonement_name + ' Atonement: '}</dt>
                                    <dd>{(gemYouHave.secondary_atonement_amount * 100).toFixed(0)}%</dd>
                                    <dt>{gemYouHave.tertiary_atonement_name + ' Atonement: '}</dt>
                                    <dd>{(gemYouHave.tertiary_atonement_amount * 100).toFixed(0)}%</dd>
                                </dl>
                            </Fragment>
                        </div>
                    </div>
                    <div className='my-4'>
                        <PrimaryButton button_label={'Replace'} on_click={() => {this.replaceGem(gemYouHave.name)}} disabled={this.props.action_disabled} />
                    </div>
                </BasicCard>
            );
        })
    }

    render() {
        return (
            <div>
                <p className='my-4'>Each card below will detail the comparison of replacing the gem at that slot. The cost is the same as attaching a gem to an empty slot.</p>

                <div className={clsx({
                    'max-h-[350px] overflow-y-scroll': this.props.when_replacing.length > 2
                })}>
                    {this.displayCards()}
                </div>

                {
                    this.state.show_replacement_comparison && this.state.gem_name_to_replace !== null ?
                        <AtonementComparison
                            is_open={true}
                            manage_modal={()=> {this.setState({
                                show_replacement_comparison: false,
                                gem_name_to_replace: null,
                            })}}
                            trading_with_seer={false}
                            original_atonement={this.props.original_atonement}
                            if_replacing={this.props.if_replacing}
                            gem_name={this.state.gem_name_to_replace}
                            update_parent={this.props.update_parent}
                            selected_gem={this.props.selected_gem}
                            selected_item={this.props.selected_item}
                            manage_parent_modal={this.props.manage_parent_modal}
                            character_id={this.props.character_id}
                        />
                    : null
                }
            </div>
        );
    }
}
