import React, {Fragment} from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import RenderAtonementDetails from "../../../../../components/gems/components/render-atonement-details";
import WarningAlert from "../../../../../../components/ui/alerts/simple-alerts/warning-alert";
import Ajax from "../../../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../../../../components/ui/progress-bars/loading-progress-bar";

export default class CharacterElementalAtonementSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            is_loading: true,
            elemental_atonement_details: [],
            error_message: '',
        };
    }

    componentDidMount(): void {
        (new Ajax).setRoute('character-sheet/'+this.props.character.id+'/elemental-atonement-info').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                is_loading: false,
                elemental_atonement_details: response.data.elemental_atonement_details,
            });
        }, (error: AxiosError) => {
            this.setState({
                is_loading: false,
            });

            if (typeof error.response !== 'undefined') {
                this.setState({
                    error_message: error.response.data.message,
                })
            }
        });
    }


    render() {

        if (this.state.is_loading) {
            return <LoadingProgressBar />
        }

        return (
            <div>
                <p className='my-4'>
                    Your atonement is calculated based off the gems you have socketed onto your gear.
                    The highest elemental value is your damage, where as the rest are used as resistances against
                    that type of elemental damage.
                </p>

                {
                    this.state.elemental_atonement_details === null ?
                        <WarningAlert>
                            You have nothing equipped. Cannot calculate your Elemental Atonement. Learn more <a href='/information/atonement' target='_blank'>here: Atonement <i
                            className="fas fa-external-link-alt"></i></a>
                        </WarningAlert>
                        :
                        <Fragment>
                            <RenderAtonementDetails original_atonement={this.state.elemental_atonement_details.elemental_atonement} />
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <h4 className='my-4'>Elemental Damage</h4>
                            <dl>
                                <dt>Element: </dt>
                                <dd>{this.state.elemental_atonement_details.elemental_atonement.highest_element.name}</dd>
                                <dt>Damage: </dt>
                                <dd>{(this.state.elemental_atonement_details.elemental_atonement.highest_element.damage * 100).toFixed(2)}%</dd>
                            </dl>
                            <p className='my-4'>Your elemental damage is a % of damage you will deal as that element in addition to your other attacks
                                when you attack an enemy. You can learn more about it <a href='/information/atonement' target='_blank'>here: Atonement <i
                                    className="fas fa-external-link-alt"></i></a></p>
                        </Fragment>
                }
            </div>
        );
    }
}
