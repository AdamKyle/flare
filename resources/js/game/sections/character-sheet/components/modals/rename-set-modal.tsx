import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import RenameSetModalProps from "../../../../lib/game/character-sheet/types/modal/rename-set-modal-props";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class RenameSetModal extends React.Component<RenameSetModalProps, any> {

    constructor(props: RenameSetModalProps) {
        super(props);

        this.state = {
            new_set_name: this.props.current_set_name,
            error_message: null,
        }
    }

    updateName(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        if (value.length > 20) {
            this.setState({
                error_message: 'Name must be shorter then 20 characters (including spaces)',
                new_set_name: value,
            })
        } else {
            this.setState({
                new_set_name: value,
                error_message: null,
            })
        }
    }

    renameSet() {
        this.props.rename_set(this.state.new_set_name)

        this.props.manage_modal();
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      secondary_actions={{
                            secondary_button_disabled: this.state.error_message !== null,
                            secondary_button_label: 'Rename',
                            handle_action: () => this.renameSet()
                      }}
            >
                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'mt-4 mb-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                <div className="mb-5">
                    <label className="label block mb-2" htmlFor="set-name">Set Name</label>
                    <input id="set-name" type="text" className="form-control" name="set-name" value={this.state.new_set_name} autoFocus onChange={this.updateName.bind(this)}/>
                    <p className='text-xs text-gray-600 dark:text-gray-400'>Names can only be 20 characters long (including spaces)</p>
                </div>
            </Dialogue>
        );
    }
}
