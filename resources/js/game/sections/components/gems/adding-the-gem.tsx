import React from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import AddingTheGemProps from "./types/adding-the-gem-props";

export default class AddingTheGem extends React.Component<AddingTheGemProps, { }> {

    constructor(props: AddingTheGemProps) {
        super(props);
    }

    canNotAddGemToItem() {
        return (this.props.socket_data.item_sockets === this.props.socket_data.current_used_slots) || this.props.action_disabled
    }

    render() {

        if (this.props.gem_to_add === null) {
            return null;
        }

        return (
            <BasicCard>
                <div className='grid grid-cols-2 gap-2 my-4'>
                    <div>
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
                    <div>
                        <h3 className='my-4'>Item Socket Data</h3>
                        <dl>
                            <dt>Item Name:</dt>
                            <dd>{this.props.socket_data.item_name}</dd>
                            <dt>Item Sockets:</dt>
                            <dd>{this.props.socket_data.item_sockets}</dd>
                            <dt>Sockets In use</dt>
                            <dd>{this.props.socket_data.current_used_slots}</dd>
                        </dl>
                    </div>
                </div>

                <div className='my-4'>
                    <PrimaryButton button_label={'Socket gem'} on_click={() => this.props.do_action('attach-gem')} disabled={this.canNotAddGemToItem()} />
                </div>
            </BasicCard>
        );
    }
}
