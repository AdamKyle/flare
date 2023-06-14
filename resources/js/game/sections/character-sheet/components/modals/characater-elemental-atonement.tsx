import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import RenderAtonementDetails from "../../../components/gems/components/render-atonement-details";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";

export default class CharacaterElementalAtonement extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Character Elemental Atonement'}
                      medium_modal={true}
            >
                <p className='my-4'>Your atonement is calculated based off the gems you have socketed onto your gear.
                    The highest elemental value is your damage, where as the rest are used as resistances against
                    that type of elemental damage.</p>

                {
                    this.props.elemental_atonement === null ?
                        <WarningAlert>
                            You have nothing equipped. Cannot calculate your Elemental Atonement. Learn more <a href='/information/atonement' target='_blank'>here: Atonement <i
                                className="fas fa-external-link-alt"></i></a>
                        </WarningAlert>
                    :
                        <Fragment>elemental_data
                            <RenderAtonementDetails original_atonement={this.props.elemental_atonement.elemental_data} />
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <h4 className='my-4'>Elemental Damage</h4>
                            <dl>
                                <dt>Element: </dt>
                                <dd>{this.props.elemental_atonement.highest_element.name}</dd>
                                <dt>Damage: </dt>
                                <dd>{(this.props.elemental_atonement.highest_element.damage * 100).toFixed(2)}%</dd>
                            </dl>
                            <p className='my-4'>Your elemental damage is a % of damage you will deal as that element in addition to your other attacks
                                when you attack an enemy. You can learn more about it <a href='/information/atonement' target='_blank'>here: Atonement <i
                                    className="fas fa-external-link-alt"></i></a></p>
                        </Fragment>
                }


            </Dialogue>
        );
    }
}
