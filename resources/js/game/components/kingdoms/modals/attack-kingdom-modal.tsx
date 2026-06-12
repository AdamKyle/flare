import { AxiosError, AxiosResponse } from "axios";
import { parseInt } from "lodash";
import React, { Fragment } from "react";
import Select from "react-select";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import Tabs from "../../../components/ui/tabs/tabs";
import Ajax from "../../../lib/ajax/ajax";
import UnitMovement from "./partials/unit-movement";
import KingdomHelpModal from "../map-pins/modals/kingdom-help-modal";
import KingdomDamageSlotItems from "../deffinitions/kingdom-damage-slot-items";
import SelectedUnitsToCallType from "../types/selected-units-to-call-type";
import AttackKingdomModalState from "../types/modals/attack-kingdom-modal-state";

export default class AttackKingdomModal extends React.Component<
    any,
    AttackKingdomModalState
> {
    private tabs: { key: string; name: string }[];

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "use-items",
                name: "Use items",
            },
            {
                key: "send-units",
                name: "Send Units",
            },
        ];

        this.state = {
            loading: false,
            fetching_data: true,
            items_to_use: [],
            kingdoms: [],
            error_message: "",
            success_message: "",
            selected_kingdoms: [],
            selected_units: [],
            selected_items: [],
            raw_item_damage: 0,
            damage_after_defence: 0,
            final_damage: 0,
            show_help_modal: false,
            help_type: "",
        };
    }

    componentDidMount() {
        new Ajax()
            .setRoute(
                "fetch-attacking-data/" +
                    this.props.kingdom_to_attack_id +
                    "/" +
                    this.props.character_id,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        items_to_use: result.data.items_to_use,
                        kingdoms: result.data.kingdoms,
                        fetching_data: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    setAmountToMove(selectedUnits: SelectedUnitsToCallType[] | []) {
        this.setState({
            selected_units: selectedUnits,
        });
    }

    setKingdoms(kingdomsSelected: number[] | []) {
        this.setState({
            selected_kingdoms: kingdomsSelected,
        });
    }

    attackKingdom() {
        this.setState(
            {
                loading: true,
                success_message: "",
                error_message: "",
            },
            () => {
                new Ajax()
                    .setRoute(
                        "attack-kingdom-with-units/" +
                            this.props.kingdom_to_attack_id +
                            "/" +
                            this.props.character_id,
                    )
                    .setParameters({
                        units_to_move: this.state.selected_units,
                    })
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
                                const response: AxiosResponse = error.response;

                                let message = response.data.message;

                                if (response.data.error) {
                                    message = response.data.error;
                                }

                                this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    }

    manageShowHelpDialogue(type: string) {
        this.setState({
            show_help_modal: !this.state.show_help_modal,
            help_type: type,
        });
    }

    useItemsOnKingdom() {
        const selectedItems = [...this.state.selected_items];

        this.setState(
            {
                loading: true,
                success_message: "",
                error_message: "",
                selected_items: [],
                raw_item_damage: 0,
                damage_after_defence: 0,
                final_damage: 0,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "drop-items-on-kingdom/" +
                            this.props.kingdom_to_attack_id +
                            "/" +
                            this.props.character_id,
                    )
                    .setParameters({
                        slots: selectedItems,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message:
                                        response.data.message ??
                                        response.data.error,
                                });
                            }

                            console.error(error);
                        },
                    );
            },
        );
    }

    buildItemsSelection() {
        return this.state.items_to_use.flatMap((slot: KingdomDamageSlotItems) =>
            Array.from({ length: slot.amount ?? 1 }, (_, index) => ({
                label:
                    slot.item.affix_name +
                    " (Amount: " +
                    (slot.amount ?? 1) +
                    ") #" +
                    (index + 1),
                value: slot.id + ":" + (index + 1),
            })),
        );
    }

    setItemsToUse(data: any) {
        const selectedItems: any = [];
        let rawDamage: number = 0;

        data.forEach((selected: { label: string; value: string }) => {
            if (selected.value !== "Please select one or more items") {
                const id = parseInt(selected.value.split(":")[0], 10) || 0;

                if (id !== 0) {
                    const foundItem = this.state.items_to_use.filter(
                        (slot: KingdomDamageSlotItems) => {
                            return slot.id === id;
                        },
                    );

                    if (foundItem.length > 0) {
                        rawDamage += foundItem[0].item.kingdom_damage;

                        selectedItems.push(id);
                    }
                }
            }
        });

        const damageAfterDefence = Math.max(
            0,
            rawDamage - this.props.kingdom_defence,
        );
        const finalDamage = Math.max(
            0,
            damageAfterDefence -
                damageAfterDefence * this.props.item_resistance,
        );

        this.setState({
            selected_items: selectedItems,
            raw_item_damage: rawDamage,
            damage_after_defence: damageAfterDefence,
            final_damage: finalDamage,
        });
    }

    getSelectedItems() {
        if (this.state.selected_items.length > 0) {
            const selectedCounts: { [key: number]: number } = {};

            return this.state.selected_items.map((slotId: number) => {
                selectedCounts[slotId] = (selectedCounts[slotId] ?? 0) + 1;
                const slot = this.state.items_to_use.find(
                    (item: KingdomDamageSlotItems) => item.id === slotId,
                );

                return {
                    label:
                        slot.item.affix_name +
                        " (Amount: " +
                        (slot.amount ?? 1) +
                        ") #" +
                        selectedCounts[slotId],
                    value: slotId + ":" + selectedCounts[slotId],
                };
            });
        }

        return [
            {
                label: "Please select one or more items",
                value: "Please select one or more items",
            },
        ];
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Attack Kingdom"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.selected_units.length === 0 ||
                        this.state.loading,
                    secondary_button_label: "Send Units",
                    handle_action: this.attackKingdom.bind(this),
                }}
                tertiary_actions={{
                    tertiary_button_disabled:
                        this.state.selected_items.length === 0 ||
                        this.state.loading,
                    tertiary_button_label: "Use items",
                    handle_action: this.useItemsOnKingdom.bind(this),
                }}
            >
                {this.state.fetching_data ? (
                    <div className="py-4">
                        <ComponentLoading />
                    </div>
                ) : (
                    <Fragment>
                        {this.state.success_message !== "" ? (
                            <SuccessAlert>
                                {this.state.success_message}
                            </SuccessAlert>
                        ) : null}

                        {this.state.error_message !== "" ? (
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}
                        <Tabs tabs={this.tabs} disabled={this.state.loading}>
                            <TabPanel key={"use-items"}>
                                <Fragment>
                                    <Select
                                        onChange={this.setItemsToUse.bind(this)}
                                        isMulti
                                        options={this.buildItemsSelection()}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base: any) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
                                        menuPortalTarget={document.body}
                                        value={this.getSelectedItems()}
                                    />
                                    <div className="my-4">
                                        <dl>
                                            <dt>Raw Item Damage</dt>
                                            <dd>
                                                {(
                                                    this.state.raw_item_damage *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Kingdom Total Defence</dt>
                                            <dd>
                                                {(
                                                    this.props.kingdom_defence *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Damage After Defence</dt>
                                            <dd>
                                                {(
                                                    this.state
                                                        .damage_after_defence *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Item Resistance</dt>
                                            <dd>
                                                {(
                                                    this.props.item_resistance *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <div className="col-span-2 my-2 border-t border-gray-300 dark:border-gray-600"></div>
                                            <dt>Final Damage</dt>
                                            <dd>
                                                {(
                                                    this.state.final_damage *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Building Damage</dt>
                                            <dd>
                                                {(
                                                    (this.state.final_damage /
                                                        2) *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Unit Damage</dt>
                                            <dd>
                                                {(
                                                    (this.state.final_damage /
                                                        2) *
                                                    100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                        </dl>
                                    </div>
                                </Fragment>
                            </TabPanel>

                            <TabPanel key={"send-units"}>
                                <UnitMovement
                                    update_units_selected={this.setAmountToMove.bind(
                                        this,
                                    )}
                                    kingdoms={this.state.kingdoms}
                                    update_kingdoms_selected={this.setKingdoms.bind(
                                        this,
                                    )}
                                />
                            </TabPanel>
                        </Tabs>

                        {this.state.loading ? <LoadingProgressBar /> : null}

                        {this.state.show_help_modal ? (
                            <KingdomHelpModal
                                manage_modal={this.manageShowHelpDialogue.bind(
                                    this,
                                )}
                                type={this.state.help_type}
                            />
                        ) : null}
                    </Fragment>
                )}
            </Dialogue>
        );
    }
}
