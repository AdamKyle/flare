import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import UsableItemSection from "./components/usable-item-section";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class InventoryUseItem extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
        }
    }

    useItem() {
        this.setState({
            loading: true,
            error_message: null,
        });

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/use-item/' + this.props.item.id)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        this.setState({
                            loading: false
                        }, () => {
                            this.props.update_inventory(result.data.inventory);

                            this.props.set_success_message(result.data.message);

                            this.props.manage_modal();
                        });
                    }, (error: AxiosError) => {
                        this.setState({loading: false});

                        if (typeof error.response !== 'undefined') {
                            const response = error.response;

                            this.setState({
                                error_message: response.data.message,
                            });
                        }
                    });
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={<span className='text-pink-500 dark:text-pink-300'>{this.props.item.item_name}</span>}
                      secondary_actions={{
                          secondary_button_disabled: false,
                          secondary_button_label: 'Use item',
                          handle_action: () => this.useItem()
                      }}
            >
                <div className="mb-5">
                    <UsableItemSection item={this.props.item} />
                    {
                        this.state.error_message !== null ?
                            <DangerAlert additional_css='my-4'>
                                {this.state.error_message}
                            </DangerAlert>
                        : null
                    }
                    {
                        this.state.loading ?
                            <LoadingProgressBar />
                        : null
                    }
                </div>
            </Dialogue>
        );
    }
}
