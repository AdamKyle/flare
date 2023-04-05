import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "../../components/gems/components/render-atonement-details";

export default class InventoryItemGemDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    atonementChanges(originalAtonement: any, equippedAtonement: any): JSX.Element|[] {
        return originalAtonement.atonements.map((atonement: any) => {

            const value = equippedAtonement.find((equipped: any) => equipped.name === atonement.name);

            const total = parseFloat(atonement.total);

            if (typeof total === 'undefined') {
                return (
                    <Fragment>
                        <dt>{atonement.name}</dt>
                        <dd className='text-green-700 dark:text-green-500'>+{(total * 100).toFixed(2)}%</dd>
                    </Fragment>
                )
            }

            if (total > value.total) {
                return (
                    <Fragment>
                        <dt>{atonement.name}</dt>
                        <dd className='text-green-700 dark:text-green-500'>+{(value.total === 0 ? total * 100 : (total - value.total) * 100).toFixed(2)}%</dd>
                    </Fragment>
                )
            }

            if (value.total < total) {
                return (
                    <Fragment>
                        <dt>{atonement.name}</dt>
                        <dd className='text-red-700 dark:text-red-500'>-{((total - value.total) * 100).toFixed(2)}%</dd>
                    </Fragment>
                )
            }

            return (
                <Fragment>
                    <dt>{atonement.name}</dt>
                    <dd>{(total * 100).toFixed(2)}%</dd>
                </Fragment>
            )
        })
    }

    renderAtonementChanges(originalAtonement: any, equippedAtonement: any) {
        return (
            <BasicCard>
                <h4 className='my-4'>{equippedAtonement.item_name} Atonement Adjustment</h4>
                <dl>
                    {this.atonementChanges(originalAtonement, equippedAtonement.data.atonements)}
                </dl>
            </BasicCard>
        )
    }

    render() {
        return (
            <div className='grid lg:grid-cols-2 gap-2'>
                <div>
                    <BasicCard>
                        <RenderAtonementDetails title={'This Items Atonement'} original_atonement={this.props.item_atonement} />
                        <h4 className='my-4'>Elemental Atonement</h4>
                        <dl>
                            <dt>Primary Element</dt>
                            <dd>{this.props.item_atonement.elemental_damage.name}</dd>
                            <dt>Elemental Damage</dt>
                            <dd>{(this.props.item_atonement.elemental_damage.amount * 100).toFixed(2)}%</dd>
                        </dl>
                    </BasicCard>
                </div>
                <div>
                    {this.renderAtonementChanges(this.props.item_atonement, this.props.equipped_atonements[0])}
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    {this.renderAtonementChanges(this.props.item_atonement, this.props.equipped_atonements[1])}
                </div>
            </div>
        )
    }
}
