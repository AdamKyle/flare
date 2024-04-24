import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ChangeNameModalProps from "../../../lib/game/kingdoms/types/modals/change-name-modal-props";
import ChangeNameState from "../../../lib/game/kingdoms/types/modals/change-name-state";

export default class ChangeNameModal extends React.Component<
    ChangeNameModalProps,
    ChangeNameState
> {
    constructor(props: any) {
        super(props);

        this.state = {
            name: this.props.name,
            loading: false,
            error_message: "",
        };
    }

    setName(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            name: e.target.value,
            error_message: "",
        });
    }

    rename() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setParameters({
                        name: this.state.name,
                    })
                    .setRoute("kingdom/" + this.props.kingdom_id + "/rename")
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                },
                                () => {
                                    this.props.handle_close();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
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

                            console.error(error);
                        },
                    );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Re-name Kingdom"}
                secondary_actions={{
                    handle_action: this.rename.bind(this),
                    secondary_button_disabled:
                        this.state.name.length === 0 ||
                        this.state.name === this.props.name,
                    secondary_button_label: "Rename Kingdom",
                }}
            >
                <div className="flex items-center mb-5">
                    <label className="w-[50px]">Name</label>
                    <div className="w-2/3">
                        <input
                            type="text"
                            value={this.state.name}
                            onChange={this.setName.bind(this)}
                            className="form-control"
                            disabled={this.state.loading}
                            minLength={5}
                            maxLength={30}
                        />
                    </div>
                </div>
                {this.state.error_message !== "" ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
