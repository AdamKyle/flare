import React, {Fragment} from "react";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import GuideQuest from "./modals/guide-quest";

export default class GuideButton extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            is_modal_open: false,
        }
    }

    componentDidMount() {
        const self = this;
        setTimeout(function(){
            if (self.props.force_open_modal) {
                self.setState({
                    is_modal_open: true,
                });
            }
        },5000);

    }

    manageGuideQuestModal() {
        this.setState({
            is_modal_open: !this.state.is_modal_open,
        });
    }

    render() {
        return (
            <Fragment>
                <SuccessOutlineButton button_label={'Guide Quests'} on_click={this.manageGuideQuestModal.bind(this)} additional_css={'mr-4'}/>

                {
                    this.state.is_modal_open ?
                        <GuideQuest
                            is_open={this.state.is_modal_open}
                            manage_modal={this.manageGuideQuestModal.bind(this)}
                            user_id={this.props.user_id}
                        />
                    : null
                }
            </Fragment>
        );
    }
}
