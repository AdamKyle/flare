import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import AbandonKingdomModalProps from "../../../lib/game/kingdoms/types/modals/abandon-kingdom-modal-props";
import AbandonKingdomModalState from "../../../lib/game/kingdoms/types/modals/abandon-kingdom-modal-state";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";

export default class AbandonKingdomModal extends React.Component<AbandonKingdomModalProps, AbandonKingdomModalState> {

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: '',
            loading: false,
        }
    }

    abandonKingdom() {
        this.setState({
            loading: true
        }, () => {
            (new Ajax()).setRoute('kingdoms/abandon/' + this.props.kingdom_id)
                .doAjaxCall('post', (response: AxiosResponse) => {
                    this.setState({
                        loading: false
                    }, () => {
                        this.props.handle_close();
                        this.props.handle_kingdom_close();
                    })
                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        let message = response.data.message;

                        if (response.data.error) {
                            message = response.data.error;
                        }

                        this.setState({
                            loading: false,
                            error_message: message,
                        });
                    }
                });
        });
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Abandon Kingdom'}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          handle_action: this.abandonKingdom.bind(this),
                          secondary_button_disabled: false,
                          secondary_button_label: 'Abandon',
                      }}
            >
                <p className='mt-4'>
                    <strong>Are you sure</strong> you want to do this? You won't be able to abandon the kingdom if:
                </p>

                <ul className='my-4 list-disc ml-5'>
                    <li>You have units in queue</li>
                    <li>You have buildings in queue</li>
                    <li>You have units in movement or are under attack or units are traveling to your kingdom</li>
                    <li>You have gold bars in the kingdom</li>
                    <li>You have already abandoned a kingdom</li>
                </ul>

                <p className='my-4'>
                    Abandoning kingdoms turns it into an NPC kingdom (yellow on the map). You cannot settle or purchase another kingdom
                    for 15 minutes AFTER you have abandoned the kingdom.
                </p>

                {
                    this.state.error_message !== '' ?
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                        : null
                }
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                        : null
                }
            </Dialogue>
        )
    }

}
