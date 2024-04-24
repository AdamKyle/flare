import React, { Fragment } from "react";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import ManageItemSockets from "./actions/manage-item-sockets";
import ManageItemSocketsActions from "./actions/manage-item-sockets-actions";
import DangerButton from "../../ui/buttons/danger-button";
import SeerCampState from "./types/seer-camp-state";
import SeerCampProps from "./types/seer-camp-props";
import SeerActions from "./ajax/seer-actions";
import ManageItemSocketsCost from "./actions/manage-item-sockets-cost";
import AddGemsToItem from "./actions/add-gems-to-item";
import AddGemsToItemActions from "./actions/add-gems-to-item-actions";
import ManageGems from "../../../sections/components/gems/manage-gems";
import RemoveGem from "./actions/remove-gem";
import Items from "./actions/deffinitions/items";
import Gems from "./actions/deffinitions/gems";

export default class SeerCamp extends React.Component<
    SeerCampProps,
    SeerCampState
> {
    constructor(props: SeerCampProps) {
        super(props);

        this.state = {
            items: [],
            gems: [],
            seer_actions: [
                {
                    label: "Please Select",
                    value: "base",
                },
                {
                    label: "Create/ReRoll Sockets",
                    value: "manage-sockets",
                },
                {
                    label: "Attach Gems",
                    value: "attach-gem",
                },
                {
                    label: "Remove Gem",
                    value: "remove-gem",
                },
            ],
            socket_cost: 2000,
            attach_gem: 500,
            remove_gem: 10,
            item_selected: 0,
            gem_selected: 0,
            is_loading: true,
            trading_with_seer: false,
            error_message: null,
            success_message: null,
            selected_seer_action: null,
            manage_gems_on_item: false,
            manage_remove_gem: false,
        };
    }

    componentDidMount() {
        SeerActions.handleInitialFetch(this);
    }

    doAction(action: string) {
        if (action === "close-seer-action") {
            this.setState({
                item_selected: 0,
                gem_selected: 0,
                error_message: null,
                success_message: null,
                selected_seer_action: null,
            });
        }

        if (action === "roll-sockets") {
            this.setState(
                {
                    error_message: null,
                    success_message: null,
                    trading_with_seer: true,
                },
                () => {
                    SeerActions.manageSocketsOnItem(
                        this,
                        this.state.item_selected,
                    );
                },
            );
        }

        if (action === "attach-gem") {
            this.setState({
                manage_gems_on_item: true,
            });
        }

        if (action === "remove-gem") {
            this.setState({
                manage_remove_gem: true,
            });
        }
    }

    isManageSocketsDisabled() {
        if (this.state.selected_seer_action === null) {
            return true;
        }

        if (this.state.item_selected === 0) {
            return true;
        }

        return false;
    }

    isAddGemsDisabled() {
        if (this.state.selected_seer_action == null) {
            return true;
        }

        if (this.state.item_selected === 0) {
            return true;
        }

        if (this.state.gem_selected === 0) {
            return true;
        }

        return false;
    }

    isLoading() {
        return this.state.trading_with_seer;
    }

    setSeerAction(data: any) {
        if (data.value === "base") {
            this.setState({
                selected_seer_action: null,
            });

            return;
        }

        this.setState({
            selected_seer_action: data.value,
        });
    }

    seerActions() {
        return this.state.seer_actions;
    }

    seerAction() {
        if (this.state.selected_seer_action === null) {
            return { label: "Please select action", value: "base" };
        }

        return this.state.seer_actions.filter((action: any) => {
            return action.value === this.state.selected_seer_action;
        })[0];
    }

    updateParent<T>(value: T, property: string) {
        this.setState((prevState) => ({
            ...prevState,
            [property]: value,
        }));
    }

    getItemInfo(key: string) {
        const item = this.state.items.filter((item: Items) => {
            return item.slot_id === this.state.item_selected;
        });

        if (item.length > 0) {
            // @ts-ignore
            const value = item[0][key];

            return value === null ? 0 : value;
        }

        return null;
    }

    buildGemDialogueTitle(gemSlotId: number): JSX.Element | null {
        let gemSlot: Gems[] | [] = this.state.gems.filter((gem: Gems) => {
            return gem.slot_id === gemSlotId;
        });

        if (gemSlot.length > 0) {
            const gem: Gems = gemSlot[0];

            return (
                <span className={"text-lime-600 dark:text-lime-500"}>
                    {gem.name}
                </span>
            );
        }

        return null;
    }

    render() {
        return (
            <Fragment>
                <div className="mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]">
                    <div className="lg:cols-start-1 lg:col-span-2">
                        {this.state.is_loading ? (
                            <LoadingProgressBar />
                        ) : (
                            <Fragment>
                                <Select
                                    onChange={this.setSeerAction.bind(this)}
                                    options={this.seerActions()}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={this.seerAction()}
                                />

                                {this.state.selected_seer_action ===
                                "manage-sockets" ? (
                                    <div className="mt-3 mb-2">
                                        <ManageItemSockets
                                            items={this.state.items}
                                            update_parent={this.updateParent.bind(
                                                this,
                                            )}
                                        />
                                    </div>
                                ) : null}

                                {this.state.selected_seer_action ===
                                "attach-gem" ? (
                                    <div className="mt-3 mb-2">
                                        <AddGemsToItem
                                            items={this.state.items}
                                            gems={this.state.gems}
                                            update_parent={this.updateParent.bind(
                                                this,
                                            )}
                                            item_selected={
                                                this.state.item_selected
                                            }
                                            gem_selected={
                                                this.state.gem_selected
                                            }
                                        />
                                    </div>
                                ) : null}

                                {this.state.selected_seer_action ===
                                "remove-gem" ? (
                                    <div className="mt-3 mb-2">
                                        <RemoveGem
                                            character_id={
                                                this.props.character_id
                                            }
                                            update_parent={this.updateParent.bind(
                                                this,
                                            )}
                                        />
                                    </div>
                                ) : null}

                                {this.state.item_selected !== 0 &&
                                this.state.selected_seer_action ===
                                    "manage-sockets" ? (
                                    <ManageItemSocketsCost
                                        socket_cost={this.state.socket_cost}
                                        get_item_info={this.getItemInfo.bind(
                                            this,
                                        )}
                                    />
                                ) : null}

                                {this.state.trading_with_seer ? (
                                    <LoadingProgressBar />
                                ) : null}
                                {this.state.error_message !== null ? (
                                    <DangerAlert additional_css={"mt-4 mb-4"}>
                                        {this.state.error_message}
                                    </DangerAlert>
                                ) : null}
                                {this.state.success_message !== null ? (
                                    <SuccessAlert additional_css={"mt-4 mb-4"}>
                                        {this.state.success_message}
                                    </SuccessAlert>
                                ) : null}
                            </Fragment>
                        )}
                    </div>
                </div>
                <div>
                    <div className="text-center lg:ml-[-100px] mt-3 mb-3">
                        {this.state.selected_seer_action ===
                        "manage-sockets" ? (
                            <Fragment>
                                <ManageItemSocketsActions
                                    do_action={this.doAction.bind(this)}
                                    is_disabled={this.isManageSocketsDisabled()}
                                    is_loading={this.state.trading_with_seer}
                                >
                                    <DangerButton
                                        button_label={"Leave Seer Camp"}
                                        on_click={this.props.leave_seer_camp}
                                        additional_css={"ml-2"}
                                        disabled={this.state.trading_with_seer}
                                    />
                                </ManageItemSocketsActions>
                            </Fragment>
                        ) : null}

                        {this.state.selected_seer_action === "attach-gem" ? (
                            <Fragment>
                                <AddGemsToItemActions
                                    do_action={this.doAction.bind(this)}
                                    is_disabled={this.isAddGemsDisabled()}
                                    is_loading={this.state.trading_with_seer}
                                >
                                    <DangerButton
                                        button_label={"Leave Seer Camp"}
                                        on_click={this.props.leave_seer_camp}
                                        additional_css={"ml-2"}
                                        disabled={this.state.trading_with_seer}
                                    />
                                </AddGemsToItemActions>
                            </Fragment>
                        ) : null}

                        {this.state.selected_seer_action === null ||
                        this.state.selected_seer_action === "remove-gem" ? (
                            <DangerButton
                                button_label={"Leave Seer Camp"}
                                on_click={this.props.leave_seer_camp}
                                additional_css={"ml-2"}
                                disabled={this.state.is_loading}
                            />
                        ) : null}
                    </div>
                </div>

                {this.state.manage_gems_on_item ? (
                    <ManageGems
                        character_id={this.props.character_id}
                        selected_item={this.state.item_selected}
                        selected_gem={this.state.gem_selected}
                        cost={this.state.attach_gem}
                        manage_model={() =>
                            this.setState({ manage_gems_on_item: false })
                        }
                        update_parent={this.updateParent.bind(this)}
                        is_open={true}
                    />
                ) : null}
            </Fragment>
        );
    }
}
