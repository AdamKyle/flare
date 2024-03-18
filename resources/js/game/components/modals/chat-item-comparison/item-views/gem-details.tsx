import React, {Fragment} from "react";
import GemBagSlotDetails from "../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";
import GemDetailsProps from "../types/gem-details-props";

export default class GemDetails extends React.Component<GemDetailsProps, {}> {

    constructor(props: GemDetailsProps) {
        super(props);
    }

    renderDetails(gem: GemBagSlotDetails): JSX.Element | null {
        return <div className="grid md:grid-cols-2 gap-2">
            <div>
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
            </div>
            <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-2'></div>
            <div>
                <dl>
                    <dt>Atoned To</dt>
                    <dd>{gem.element_atoned_to}</dd>
                    <dt>Atoned Amount</dt>
                    <dd>{(gem.element_atoned_to_amount * 100).toFixed(0)}%</dd>
                    <dt>Strong Against</dt>
                    <dd>{gem.strong_against}</dd>
                    <dt>Weak Against</dt>
                    <dd>{gem.weak_against}</dd>
                </dl>
            </div>
        </div>
    }

    getGem() {
        return this.props.gem;
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
                {this.renderDetails(this.getGem())}
            </Fragment>
        )
    }
}
