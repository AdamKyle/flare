import React from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class AddingTheGem extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className='my-4'>
                    <h3 className='my-4 text-lime-600 dark:text-lime-500'>{this.props.gem_to_add.name}</h3>
                    <dl>
                        <dt>Tier</dt>
                        <dd>{this.props.gem_to_add.tier}</dd>
                        <dt>{this.props.gem_to_add.primary_atonement_name + ' Atonement: '}</dt>
                        <dd>{(this.props.gem_to_add.primary_atonement_amount * 100).toFixed(0)}%</dd>
                        <dt>{this.props.gem_to_add.secondary_atonement_name + ' Atonement: '}</dt>
                        <dd>{(this.props.gem_to_add.secondary_atonement_amount * 100).toFixed(0)}%</dd>
                        <dt>{this.props.gem_to_add.tertiary_atonement_name + ' Atonement: '}</dt>
                        <dd>{(this.props.gem_to_add.tertiary_atonement_amount * 100).toFixed(0)}%</dd>
                    </dl>
                </div>
                <div className='my-4'>
                    <PrimaryButton button_label={'Socket gem'} on_click={() => this.props.do_action('attach-gem')} disabled={this.props.action_disabled} />
                </div>
            </BasicCard>
        );
    }
}
