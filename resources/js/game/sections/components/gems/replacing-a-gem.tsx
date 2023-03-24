import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import GemComparisonDetails from "./gem-comparison-details";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import clsx from "clsx";

export default class ReplacingAGem extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    displayCards() {
        return this.props.when_replacing.map((gemComparisonDetails: any) => {
            const gemYouHave = this.props.gems_you_have.filter((gem: any) => {
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
                        <PrimaryButton button_label={'Replace'} on_click={() => {}} disabled={this.props.action_disabled} />
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
            </div>
        );
    }
}
