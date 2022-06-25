import React, {Fragment} from "react";
import KingdomDetailsProps from "../../lib/game/kingdoms/types/kingdom-details-props";
import {formatNumber} from "../../lib/game/format-number";

export default class KingdomDetails extends React.Component<KingdomDetailsProps, any> {
    constructor(props: KingdomDetailsProps) {
        super(props);
    }

    calculateTotalDefence(): number {
        const kingdom = this.props.kingdom;

        return kingdom.walls_defence + kingdom.treasury_defence +
               kingdom.gold_bars_defence + kingdom.passive_defence +
               kingdom.defence_bonus;
    }

    render() {
        return (
            <Fragment>
                <div className='grid md:grid-cols-2 gap-4'>
                    <div>
                        <h3>Basics</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Name</dt>
                            <dd>{this.props.kingdom.name}</dd>
                            <dt>Morale</dt>
                            <dd>{
                                (this.props.kingdom.current_morale * 100).toFixed(2) + '/100 %'
                            }</dd>
                            <dt>Population</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.current_population ) + '/' +
                                formatNumber(this.props.kingdom.max_population)
                            }</dd>
                            <dt>Treasury</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.treasury)
                            }</dd>
                            <dt>Gold Bars</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.gold_bars)
                            }</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Resources</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Stone</dt>
                            <dd>{formatNumber(this.props.kingdom.current_stone) + '/' + formatNumber(this.props.kingdom.max_stone)}</dd>
                            <dt>Clay</dt>
                            <dd>{formatNumber(this.props.kingdom.current_clay) + '/' + formatNumber(this.props.kingdom.max_clay)}</dd>
                            <dt>Wood</dt>
                            <dd>{formatNumber(this.props.kingdom.current_wood) + '/' + formatNumber(this.props.kingdom.max_wood)}</dd>
                            <dt>Iron</dt>
                            <dd>{formatNumber(this.props.kingdom.current_iron) + '/' + formatNumber(this.props.kingdom.max_iron)}</dd>
                        </dl>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <div>
                    <h3>Defence Break Down</h3>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <dl>
                        <dt>Wall Defence</dt>
                        <dd>{(this.props.kingdom.walls_defence * 100).toFixed(2)}%</dd>
                        <dt>Treasury Defence</dt>
                        <dd>{(this.props.kingdom.treasury_defence * 100).toFixed(2)}%</dd>
                        <dt>Gold Bars Defence</dt>
                        <dd>{(this.props.kingdom.gold_bars_defence * 100).toFixed(2)}%</dd>
                        <dt>Passive Defence</dt>
                        <dd>{(this.props.kingdom.passive_defence * 100).toFixed(2)}%</dd>
                        <dt>Defence Bonus</dt>
                        <dd>{(this.props.kingdom.defence_bonus * 100).toFixed(2)}%</dd>
                        <dt>Total Defence</dt>
                        <dd>{(this.calculateTotalDefence() * 100).toFixed(2)}%</dd>
                    </dl>
                </div>
            </Fragment>

        )
    }
}
