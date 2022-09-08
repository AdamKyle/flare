import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import clsx from "clsx";
import { formatNumber } from "../../lib/game/format-number";

export default class KingdomLogDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderBuildingChanges() {
        const changes: any = [];

        this.props.log.old_buildings.forEach((oldBuilding: { name: string; durability: number; }) => {
           let foundNewBuilding = this.props.log.new_buildings.filter((newBuilding: { name: string; durability: number; }) => newBuilding.name === oldBuilding.name);

           if (foundNewBuilding.length > 0) {
               foundNewBuilding = foundNewBuilding[0];

               if (foundNewBuilding.durability === oldBuilding.durability) {
                   changes.push(
                       <Fragment>
                           <dt>{oldBuilding.name}</dt>
                           <dd>0% Lost{this.props.log.is_mine ? ', New Durability: ' + formatNumber(foundNewBuilding.durability) : null}</dd>
                       </Fragment>
                   );
               } else {
                   changes.push(
                       <Fragment>
                           <dt>{oldBuilding.name}</dt>
                           <dd className='text-red-600 dark:text-red-400'>
                               {((foundNewBuilding.durability/oldBuilding.durability) * 100).toFixed(0)}% Lost{
                               this.props.log.is_mine ? ', New Durability: ' + formatNumber(foundNewBuilding.durability) : null
                           }
                           </dd>
                       </Fragment>
                   );
               }
           }
        });

        return changes;
    }

    renderUnitChanges() {
        const changes: any = [];

        this.props.log.old_units.forEach((oldUnit: { name: string; amount: number; }) => {
            let foundNewUnit = this.props.log.new_units.filter((newUnit: { name: string; amount: number; }) => newUnit.name === oldUnit.name);

            if (foundNewUnit.length > 0) {
                foundNewUnit = foundNewUnit[0];

                if (foundNewUnit.amount === oldUnit.amount) {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd>0% Lost{this.props.log.is_mine ? ', Amount Left: ' +  formatNumber(foundNewUnit.amount) : null}</dd>
                        </Fragment>
                    );
                } else {
                    changes.push(
                        <Fragment>
                            <dt>{oldUnit.name}</dt>
                            <dd className='text-red-600 dark:text-red-400'>
                                {((foundNewUnit.amount/oldUnit.amount) * 100).toFixed(0)}% Lost{this.props.log.is_mine ? ', Amount Left: ' + formatNumber(foundNewUnit.amount) : null}
                            </dd>
                        </Fragment>
                    );
                }
            }
        });

        return changes;
    }

    render() {
        return (
            <BasicCard>
                <div className='text-right cursor-pointer text-red-500'>
                    <button onClick={this.props.close_details}><i className="fas fa-minus-circle"></i></button>
                </div>
                <div className='my-4'>
                    <h3 className='mb-4'>{this.props.log.status}</h3>

                    <dl>
                        <dt>Kingdom Attacked (X/Y)</dt>
                        <dd className={
                            clsx({
                                'text-green-600 dark:text-green-400': !this.props.is_mine,
                                'text-red-600 dark:text-red-400': !this.props.is_mine
                            })
                        }>
                            {this.props.log.to_kingdom_name} {this.props.log.to_x} / {this.props.log.to_y}
                        </dd>
                        <dt>Attacked From (X/Y)</dt>
                        <dd>
                            {
                                this.props.log.from_kingdom_name !== null ?
                                    this.props.from_kingdom_name + ' ' + this.props.log.from_x + '/' + this.props.log.from_y
                                :
                                    'N/A'
                            }
                        </dd>
                        <dt>Kingdom Attacked Morale Loss</dt>
                        <dd className='text-red-600 dark:text-red-400'>{(this.props.log.morale_loss * 100).toFixed(2)} %</dd>
                    </dl>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <div className='grid md:grid-cols-2 gap-2'>
                        <div>
                            <h3 className='mb-4'>
                                Building Changes
                            </h3>
                            <dl>
                                {this.renderBuildingChanges()}
                            </dl>
                        </div>
                        {
                            this.props.log.old_units.length === 0 && this.props.log.new_units.length === 0 ?
                                <div>
                                    <h3 className='mb-4'>
                                        Unit Changes
                                    </h3>
                                    <p>There were no changes in kingdom units.</p>
                                </div>
                            :
                                <div>
                                    <h3 className='mb-4'>
                                        Unit Changes
                                    </h3>
                                    <dl>
                                        {this.renderUnitChanges()}
                                    </dl>
                                </div>
                        }
                    </div>
                </div>
            </BasicCard>
        )
    }
}
