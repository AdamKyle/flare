import React, { Fragment } from "react";
import {
    craftingGetEndPoints,
    craftingPostEndPoints,
} from "../general-crafting/helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { formatNumber } from "../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import CraftingXp from "../base-components/skill-xp/crafting-xp";

export default class Alchemy extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
            loading: false,
            craftable_items: [],
            skill_xp: {
                curent_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
        };
    }

    componentDidMount() {
        const url = craftingGetEndPoints("alchemy", this.props.character_id);

        new Ajax().setRoute(url).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    craftable_items: result.data.items,
                    skill_xp: result.data.skill_xp,
                });
            },
            (error: AxiosError) => {},
        );
    }

    setItemToCraft(data: any) {
        const foundItem = this.state.craftable_items.filter((item: any) => {
            return item.id === parseInt(data.value);
        });

        if (foundItem.length > 0) {
            this.setState({
                selected_item: foundItem[0],
            });
        }
    }

    buildItems() {
        return this.state.craftable_items.map((item: any) => {
            return {
                label:
                    item.name +
                    ", Gold Dust Cost: " +
                    formatNumber(item.gold_dust_cost) +
                    " Shards Cost: " +
                    formatNumber(item.shards_cost),
                value: item.id,
            };
        });
    }

    defaultItem() {
        if (this.state.selected_item !== null) {
            const item = this.state.selected_item;

            return {
                label:
                    item.name +
                    ", Gold Dust Cost: " +
                    formatNumber(item.gold_dust_cost) +
                    " Shards Cost: " +
                    formatNumber(item.shards_cost),
                value: item.id,
            };
        }

        return { label: "Please select item to craft", value: 0 };
    }

    craft() {
        this.setState(
            {
                loading: true,
            },
            () => {
                const url = craftingPostEndPoints(
                    "alchemy",
                    this.props.character_id,
                );

                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        item_to_craft: this.state.selected_item.id,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            const oldItems = JSON.parse(
                                JSON.stringify(this.state.craftable_items),
                            );

                            this.setState(
                                {
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.skill_xp,
                                },
                                () => {
                                    if (!isEqual(oldItems, result.data.items)) {
                                        generateServerMessage(
                                            "new_items",
                                            "You have new Alchemy items to craft. Check the list!",
                                        );
                                    }
                                },
                            );
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    render() {
        return (
            <Fragment>
                <div className="mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]">
                    <div className="cols-start-1 col-span-2">
                        <Select
                            onChange={this.setItemToCraft.bind(this)}
                            options={this.buildItems()}
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
                            value={this.defaultItem()}
                        />

                        {this.state.loading ? <LoadingProgressBar /> : null}

                        {this.state.craftable_items.length > 0 ? (
                            <CraftingXp skill_xp={this.state.skill_xp} />
                        ) : null}
                    </div>
                </div>
                <div className={"text-center md:ml-[-100px] mt-3 mb-3"}>
                    <PrimaryButton
                        button_label={"Transmute"}
                        on_click={this.craft.bind(this)}
                        disabled={
                            this.state.loading ||
                            this.state.selected_item === null ||
                            this.props.cannot_craft
                        }
                    />
                    <DangerButton
                        button_label={"Close"}
                        on_click={this.clearCrafting.bind(this)}
                        additional_css={"ml-2"}
                        disabled={this.state.loading || this.props.cannot_craft}
                    />
                    <a
                        href="/information/alchemy"
                        target="_blank"
                        className="ml-2"
                    >
                        Help <i className="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </Fragment>
        );
    }
}
