import React, {Fragment} from "react";
import GemBagSlotDetails from "../../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";

export default class GemDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderDetails(gem: GemBagSlotDetails): JSX.Element | null {
        return <Fragment>
            <dl>
                <dt>Tier</dt>
                <dd>{gem.tier}</dd>
                <dt>{gem.primary_atonement_name + ' Atonement: '}</dt>
                <dd>{(gem.primary_atonement_amount * 100).toFixed(0)}%</dd>
                <dt>{gem.secondary_atonement_name + ' Atonement: '}</dt>
                <dd>{(gem.secondary_atonement_amount * 100).toFixed(0)}%</dd>
                <dt>{gem.tertiary_atonement_name + ' Atonement: '}</dt>
                <dd>{(gem.tertiary_atonement_amount * 100).toFixed(0)}%</dd>
            </dl>
        </Fragment>
    }

    render() {
        return (
            <Fragment>
                <div>
                    <p className='mb-2 mt-2'>
                        Atonement refers to the elemental resistance against Raid and PVP battles.
                        It can also refer to the amount of elemental damage you do per hit, which is a % of your damage.
                    </p>
                    <p className='mb-2 mt-2'>
                        When determining the elemental damage %, we take the highest elemental atonement %.
                        Checkout <a href='#'>Gem Crafting</a> for more info.
                    </p>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                {this.renderDetails(this.props.gem)}
            </Fragment>
        )
    }
}
