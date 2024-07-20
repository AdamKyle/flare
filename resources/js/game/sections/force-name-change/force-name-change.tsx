import React, { Fragment } from "react";
import Dialogue from "../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../components/ui/loading/component-loading";
import ItemDetails from "../character-sheet/components/modals/components/item-details";
import Ajax from "../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";

export default class ForceNameChange extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            new_name: "",
            error_message: "",
            loading: false,
        };
    }

    changeName() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            this.props.character_id +
                            "/name-change",
                    )
                    .setParameters({
                        name: this.state.new_name,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            location.reload();
                        },
                        (error: AxiosError) => {
                            let response = null;

                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.errors.name[0],
                                });
                            }
                        },
                    );
            },
        );
    }

    closeErrorMessage() {
        this.setState({
            error_message: "",
        });
    }

    updateName(e: React.ChangeEvent<HTMLInputElement>) {
        const name = e.target.value;

        if (name.length > 15) {
            this.setState({
                error_message: "Name is above the 15 character limit.",
                new_name: name,
            });
        } else if (name.length < 5) {
            this.setState({
                error_message: "Name is below the 5 character limit.",
                new_name: name,
            });
        } else {
            this.setState({
                new_name: name,
                error_message: "",
            });
        }
    }

    manageModal() {}

    render() {
        return (
            <Dialogue
                is_open={true}
                handle_close={this.manageModal}
                primary_button_disabled={true}
                title={"Force Name Change"}
                secondary_actions={{
                    secondary_button_label: "Change Name",
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.new_name === "" ||
                        this.state.error_message !== "",
                    handle_action: this.changeName.bind(this),
                }}
            >
                <div className="mb-5 relative">
                    {this.state.error_message !== "" ? (
                        <DangerAlert
                            close_alert={this.closeErrorMessage.bind(this)}
                            additional_css={"mb-5"}
                        >
                            {this.state.error_message}
                        </DangerAlert>
                    ) : null}
                    <p className="mb-5">
                        The Creator has decided that your name violates the
                        rules of the game or is offensive to other players. You
                        are being forced to change your name. Even if you logout
                        and back in, you will still see this modal. Failure to
                        change your name, or use of third party tools to get
                        around this, will result in an immediate ban.
                    </p>

                    <div className="mb-5">
                        <label className="label block mb-2" htmlFor="set-name">
                            New Character Name
                        </label>
                        <input
                            id="set-name"
                            type="text"
                            className="form-control"
                            name="set-name"
                            value={this.state.new_name}
                            autoFocus
                            onChange={this.updateName.bind(this)}
                        />
                        <p className="text-xs text-gray-600 dark:text-gray-400">
                            Character names may not contain spaces an can only
                            be 15 characters long (5 characters min) and only
                            contain letters and numbers (of any case).
                        </p>
                    </div>

                    {this.state.loading ? <LoadingProgressBar /> : null}
                </div>
            </Dialogue>
        );
    }
}
