import React from "react";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../lib/containers/core-container";
import KingdomResourceTransferAjax from "./ajax/kingdom-resource-transfer-ajax";
import { formatNumber } from "../../lib/game/format-number";
import PrimaryButton from "../ui/buttons/primary-button";
import DropDown from "../ui/drop-down/drop-down";
import DangerButton from "../ui/buttons/danger-button";
import { startCase } from "lodash";
import SuccessButton from "../ui/buttons/success-button";
import Select from "react-select";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";

export default class KingdomResourceTransfer extends React.Component<any, any> {
    private kingdomResourceTransferRequestAjax: KingdomResourceTransferAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            success_message: null,
            loading: true,
            requesting: false,
            kingdoms: [],
            index_to_view: 0,
            can_go_back: false,
            can_go_forward: true,
            amount_of_resources: "",
            type: null,
            use_air_ship: false,
        };

        this.kingdomResourceTransferRequestAjax = serviceContainer().fetch(
            KingdomResourceTransferAjax,
        );
    }

    componentDidMount() {
        this.kingdomResourceTransferRequestAjax.fetchKingdomsToTransferFrom(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );
    }

    setAmountToRequest(e: React.ChangeEvent<HTMLInputElement>) {
        let value = parseInt(e.target.value) || 0;

        if (value > 5_000 && !this.state.use_air_ship) {
            value = 5000;
        }

        if (value > 10000 && this.state.use_air_ship) {
            value = 10000;
        }

        this.setState(
            {
                amount_of_resources: value > 0 ? value : "",
            },
            () => {
                if (value > 0) {
                    this.setState({
                        can_go_back: false,
                        can_go_forward: false,
                    });
                } else {
                    this.setState({
                        can_go_back: false,
                        can_go_forward: true,
                    });
                }
            },
        );
    }

    useAirShip(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.checked;

        this.setState(
            {
                use_air_ship: value,
            },
            () => {
                if (value) {
                    this.setState({
                        can_go_back: false,
                        can_go_forward: false,
                    });
                } else if (this.state.amount_of_resources === "") {
                    this.setState({
                        can_go_back: false,
                        can_go_forward: true,
                    });
                }
            },
        );
    }

    setTypeOfResourceToRequest(type: string) {
        this.setState({
            type: type,
        });
    }

    clearEntry() {
        this.setState({
            type: null,
            amount_of_resources: "",
            use_air_ship: false,
            can_go_back: false,
            can_go_forward: true,
        });
    }

    goForward() {
        let newIndex = this.state.index_to_view + 1;

        if (typeof this.state.kingdoms[newIndex] !== "undefined") {
            this.setState({
                index_to_view: newIndex,
                can_go_back: this.canGoBackward(newIndex),
                can_go_forward: this.canGoForward(newIndex),
            });
        }
    }

    goBack() {
        let newIndex = this.state.index_to_view - 1;

        if (typeof this.state.kingdoms[newIndex] !== "undefined") {
            this.setState({
                index_to_view: newIndex,
                can_go_back: this.canGoBackward(newIndex),
                can_go_forward: this.canGoForward(newIndex),
            });
        }
    }

    canGoForward(index: number) {
        return typeof this.state.kingdoms[index + 1] !== "undefined";
    }

    canGoBackward(index: number) {
        return typeof this.state.kingdoms[index - 1] !== "undefined";
    }

    createKingdomNameOptions() {
        return this.state.kingdoms.map((kingdom: any, index: number) => {
            return {
                label: kingdom.kingdom_name,
                value: index,
            };
        });
    }

    setKingdomToView(data: any) {
        const index = parseInt(data.value) || 0;

        if (index > 0) {
            if (typeof this.state.kingdoms[index] !== "undefined") {
                this.setState({
                    index_to_view: index,
                    can_go_back: this.canGoBackward(index),
                    can_go_forward: this.canGoForward(index),
                });
            }
        }
    }

    defaultKingdomSelection() {
        const kingdom = this.state.kingdoms[this.state.index_to_view];

        return {
            label: kingdom.kingdom_name,
            value: this.state.index_to_view,
        };
    }

    renderKingdomDetailsForIndex(index: number) {
        const kingdom = this.state.kingdoms[index];
        return (
            <div className="grid md:grid-cols-2 gap-2">
                <dl>
                    <dt>Name</dt>
                    <dd>
                        <Select
                            onChange={this.setKingdomToView.bind(this)}
                            options={this.createKingdomNameOptions()}
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
                            value={this.defaultKingdomSelection()}
                        />
                    </dd>
                    <dt>X/Y</dt>
                    <dd>
                        {kingdom.x_position}/{kingdom.y_position}
                    </dd>
                    <dt>Time to travel</dt>
                    <dd>{kingdom.time_to_travel} Minutes</dd>
                </dl>
                <div className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <dl>
                    <dt>Current Wood</dt>
                    <dd>{formatNumber(kingdom.current_wood)}</dd>
                    <dt>Current Clay</dt>
                    <dd>{formatNumber(kingdom.current_clay)}</dd>
                    <dt>Current Stone</dt>
                    <dd>{formatNumber(kingdom.current_stone)}</dd>
                    <dt>Current Iron</dt>
                    <dd>{formatNumber(kingdom.current_iron)}</dd>
                    <dt>Current Steel</dt>
                    <dd>{formatNumber(kingdom.current_steel)}</dd>
                </dl>
            </div>
        );
    }

    sendOffRequest() {
        const kingdom = this.state.kingdoms[this.state.index_to_view];

        const params = {
            amount_of_resources: this.state.amount_of_resources,
            type_of_resource: this.state.type,
            use_air_ship: this.state.use_air_ship,
            kingdom_requesting: this.props.kingdom_id,
            kingdom_requesting_from: kingdom.kingdom_id,
        };

        this.setState(
            {
                success_message: null,
                error_message: null,
                requesting: true,
            },
            () => {
                this.kingdomResourceTransferRequestAjax.requestResources(
                    this,
                    params,
                    this.props.character_id,
                );
            },
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.kingdoms.length <= 0) {
            return (
                <p>
                    You have no other kingdoms on this plane to request
                    resources for. Or you have no other kingdoms on this plane,
                    who have Market Places built.
                </p>
            );
        }

        return (
            <div>
                <h3>Kingdom Resource Request</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p>
                    You can only request resources from one kingdom at a time.
                    Below is a list of kingdoms that have resources you can
                    request from. By default players can request a max of 5,000
                    resources of any or all types. If the kingdom you request
                    from, has Airships, you can use one and increase the max to
                    10,000 at a time.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-3"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.success_message !== null ? (
                    <SuccessAlert additional_css={"my-3"}>
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                <div className="max-w-full md:max-w-[75%] md:mr-auto md:ml-auto">
                    {this.state.kingdoms.length > 0 ? (
                        <div>
                            {this.renderKingdomDetailsForIndex(
                                this.state.index_to_view,
                            )}
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <p className="text-red-700 dark:text-red-500 italic my-2 text-center">
                                These two fields are the only required fields.
                            </p>
                            <div className="flex items-center mb-5">
                                <label className="w-1/3">
                                    Amount to transfer
                                </label>
                                <div className="w-1/3">
                                    <input
                                        type="number"
                                        value={this.state.amount_of_resources}
                                        onChange={this.setAmountToRequest.bind(
                                            this,
                                        )}
                                        className="form-control"
                                        disabled={this.state.requesting}
                                        min={0}
                                    />
                                </div>
                                <div className="w-1/3">
                                    <div className="ml-2">
                                        <DropDown
                                            menu_items={[
                                                {
                                                    name: "Wood",
                                                    icon_class:
                                                        "fas fa-shopping-bag",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "wood",
                                                        ),
                                                },
                                                {
                                                    name: "Clay",
                                                    icon_class:
                                                        "ra ra-bubbling-potion",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "clay",
                                                        ),
                                                },
                                                {
                                                    name: "Stone",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "stone",
                                                        ),
                                                },
                                                {
                                                    name: "Iron",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "iron",
                                                        ),
                                                },
                                                {
                                                    name: "Steel",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "steel",
                                                        ),
                                                },
                                                {
                                                    name: "All",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () =>
                                                        this.setTypeOfResourceToRequest(
                                                            "all",
                                                        ),
                                                },
                                            ]}
                                            button_title={
                                                "Selected: " +
                                                (this.state.type ?? "None")
                                            }
                                            selected_name={""}
                                            disabled={this.state.requesting}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <div className="flex items-center mb-5">
                                <div className="w-1/3">
                                    <label>Use Air Ships?</label>
                                </div>
                                <div>
                                    <input
                                        type="checkbox"
                                        onChange={this.useAirShip.bind(this)}
                                        className="form-checkbox"
                                        disabled={this.state.requesting}
                                    />
                                </div>
                            </div>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <h5>Resources to transfer</h5>
                            <dl>
                                <dt>Amount:</dt>
                                <dd className="text-green-700 dark:text-green-500">
                                    {this.state.amount_of_resources > 0
                                        ? "+"
                                        : ""}
                                    {formatNumber(
                                        this.state.amount_of_resources > 0
                                            ? this.state.amount_of_resources
                                            : 0,
                                    )}
                                </dd>
                                <dt>For Resource:</dt>
                                <dd className="text-orange-700 dark:text-orange-500">
                                    {this.state.type === null
                                        ? "None selected"
                                        : startCase(this.state.type)}
                                </dd>
                                <dt>Use Air Ship</dt>
                                <dd>
                                    {this.state.use_air_ship ? "Yes" : "No"}
                                </dd>
                                <dt>Population Cost</dt>
                                <dd>50</dd>
                                <dt>Spearmen Cost</dt>
                                <dd>75</dd>
                            </dl>
                            <p className="my-2">
                                When sending resources, you will also send a
                                "gaurd" with the resources. They will return if
                                the resources are delivered. They can be killed
                                if the kingdom to be delivered to is no longer
                                in your control.
                            </p>
                            {this.state.requesting ? (
                                <LoadingProgressBar />
                            ) : null}
                            <DangerButton
                                button_label={"Clear"}
                                on_click={this.clearEntry.bind(this)}
                                additional_css={"my-3"}
                                disabled={this.state.requesting}
                            />
                            <SuccessButton
                                button_label={"Request"}
                                on_click={this.sendOffRequest.bind(this)}
                                additional_css={"my-3 ml-2"}
                                disabled={
                                    !(
                                        this.state.amount_of_resources !== "" &&
                                        this.state.type !== null
                                    ) || this.state.requesting
                                }
                            />
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <div className="my-4 flex justify-between">
                                <PrimaryButton
                                    button_label={"Previous"}
                                    on_click={this.goBack.bind(this)}
                                    disabled={
                                        !this.state.can_go_back ||
                                        this.state.requesting
                                    }
                                />
                                <PrimaryButton
                                    button_label={"Next"}
                                    on_click={this.goForward.bind(this)}
                                    disabled={
                                        !this.state.can_go_forward ||
                                        this.state.requesting
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <p>
                            There are no kingdoms to request resources from.
                            Settle some more.
                        </p>
                    )}
                </div>
            </div>
        );
    }
}
