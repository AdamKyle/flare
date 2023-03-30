import React, {Fragment} from "react";
import PrimaryButton from "../../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../../components/ui/buttons/danger-button";

export default class ManageItemSocketsActions extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    doAction() {
        this.props.do_action('roll-sockets')
    }

    closeAction() {
        this.props.do_action('close-seer-action');
    }

    render() {
        return(
            <Fragment>
                <PrimaryButton button_label={'Roll Sockets'}
                               on_click={this.doAction.bind(this)}
                               disabled={this.props.is_disabled || this.props.is_loading}
                />
                {
                    this.props.children
                }
                <a href='/information/seer' target='_blank' className='relative top-[20px] md:top-[0px] ml-2'>Help <i
                    className="fas fa-external-link-alt"></i></a>
            </Fragment>
        );
    }
}
