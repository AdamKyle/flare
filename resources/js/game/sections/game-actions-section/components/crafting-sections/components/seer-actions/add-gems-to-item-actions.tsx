import React, {Fragment} from "react";
import PrimaryButton from "../../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../../components/ui/buttons/danger-button";

export default class AddGemsToItemActions extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    viewGem() {
        this.props.do_action('view-gem')
    }

    compareGem() {
        this.props.do_action('compare-gem')
    }

    attachGem() {
        this.props.do_action('attach-gem')
    }

    closeAction() {
        this.props.do_action('close-seer-action');
    }

    render() {
        return(
            <Fragment>
                <PrimaryButton button_label={'Attach Gem'}
                               on_click={this.attachGem.bind(this)}
                               disabled={this.props.is_disabled || this.props.is_loading}
                               additional_css={'ml-2'}
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
