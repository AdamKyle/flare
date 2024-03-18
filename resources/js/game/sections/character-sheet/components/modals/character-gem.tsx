import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import GemDetails from "../../../../components/modals/item-details/item-views/gem-details";

export default class CharacterGem extends React.Component<any, any> {


    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            gem_details: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/gem-details/' + this.props.slot_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                gem_details: result.data
            });
        }, (error: AxiosError) => {
            console.error(error);
        })
    }


    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
            >
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    :
                        <GemDetails gem={this.state.gem_details} />
                }
            </Dialogue>
        );
    }
}
