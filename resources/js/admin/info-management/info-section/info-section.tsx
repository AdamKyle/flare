import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import Select from "react-select";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import { isEqual, debounce } from "lodash";
import ComponentLoading from "../../../game/components/ui/loading/component-loading";
import SuccessButton from "../../../game/components/ui/buttons/success-button";
import OrangeButton from "../../../game/components/ui/buttons/orange-button";
import { Editor } from "@tinymce/tinymce-react";

export default class InfoSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            content: "",
            selected_live_wire_component: null,
            selected_item_table_type: null,
            image_to_upload: null,
            order: "",
            loading: true,
        };

        this.setValue = debounce(this.setValue.bind(this), 300);
    }

    componentDidMount() {
        const self = this;

        setTimeout(function () {
            self.setState({
                content: self.props.content.content,
                selected_live_wire_component:
                    self.props.content.live_wire_component,
                selected_item_table_type: self.props.content.item_table_type,
                image_to_upload: null,
                order: self.props.content.order,
                loading: false,
            });
        }, 500);
    }

    componentDidUpdate(prevProps: Readonly<any>) {
        if (!isEqual(this.props.content.content, prevProps.content.content)) {
            this.setState({
                content: this.props.content.content,
            });
        }
    }

    setValue(data: any) {
        this.setState(
            {
                content: data,
            },
            () => {
                this.updateParentElement();
            },
        );
    }

    setLivewireComponent(data: any) {
        this.setState(
            {
                selected_live_wire_component:
                    data.value !== "" ? data.value : null,
            },
            () => {
                this.updateParentElement();
            },
        );
    }

    setItemTableType(data: any) {
        this.setState(
            {
                selected_item_table_type: data.value !== "" ? data.value : null,
            },
            () => {
                this.updateParentElement();
            },
        );
    }

    setOrder(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState(
            {
                order: e.target.value,
            },
            () => {
                this.updateParentElement();
            },
        );
    }

    updateParentElement() {
        this.props.update_parent_element(this.props.index, {
            live_wire_component: this.state.selected_live_wire_component,
            item_table_type: this.state.selected_item_table_type,
            content: this.state.content,
            content_image_path: this.state.image_to_upload,
            order: this.state.order,
        });
    }

    removeSection() {
        this.props.remove_section(this.props.index);
    }

    buildOptions() {
        return [
            {
                label: "Please select",
                value: "",
            },
            {
                label: "Items",
                value: "admin.items.items-table",
            },
            {
                label: "Races",
                value: "admin.races.races-table",
            },
            {
                label: "Classes",
                value: "admin.classes.classes-table",
            },
            {
                label: "Monsters",
                value: "admin.monsters.monsters-table",
            },
            {
                label: "Celestials",
                value: "admin.monsters.celestials-table",
            },
            {
                label: "Quest items",
                value: "info.quest-items.quest-items-table",
            },
            {
                label: "Crafting Books",
                value: "info.quest-items.crafting-books-table",
            },
            {
                label: "Craftable Items",
                value: "info.items.craftable-items-table",
            },
            {
                label: "Hell Forged Items",
                value: "info.items.hell-forged",
            },
            {
                label: "Purgatory Chains Items",
                value: "info.items.purgatory-chains",
            },
            {
                label: "Pirate Lord Leather",
                value: "info.items.pirate-lord-leather",
            },
            {
                label: "Corrupted Ice",
                value: "info.items.corrupted-ice",
            },
            {
                label: "Twisted Earth",
                value: "info.items.twisted-earth",
            },
            {
                label: "Delusional Silver",
                value: "info.items.delusional-silver",
            },
            {
                label: "Ancestral Items",
                value: "info.items.ancestral-items",
            },
            {
                label: "Craftable Trinkets",
                value: "info.items.craftable-trinkets",
            },
            {
                label: "Enchantments",
                value: "admin.affixes.affixes-table",
            },
            {
                label: "Alchemy Items",
                value: "info.alchemy-items.alchemy-items-table",
            },
            {
                label: "Alchemy Holy Items",
                value: "info.alchemy-items.alchemy-holy-items-table",
            },
            {
                label: "Alchemy Kingdom Damaging Items",
                value: "info.alchemy-items.alchemy-kingdom-items-table",
            },
            {
                label: "Skills",
                value: "admin.skills.skills-table",
            },
            {
                label: "Class Skills",
                value: "info.skills.class-skills",
            },
            {
                label: "Maps",
                value: "admin.maps.maps-table",
            },
            {
                label: "NPCs",
                value: "admin.npcs.npc-table",
            },
            {
                label: "Kingdom Passive Skills",
                value: "admin.passive-skills.passive-skill-table",
            },
            {
                label: "Kingdom Building",
                value: "admin.kingdoms.buildings.buildings-table",
            },
            {
                label: "Kingdom Units",
                value: "admin.kingdoms.units.units-table",
            },
            {
                label: "Regular Locations",
                value: "info.locations.regular-locations",
            },
            {
                label: "Special Locations",
                value: "info.locations.special-locations",
            },
            {
                label: "Class Specials",
                value: "admin.class-specials.class-specials-table",
            },
            {
                label: "Raids",
                value: "admin.raids.raids-table",
            },
        ];
    }

    buildItemTableTypes() {
        return [
            {
                label: "Please select",
                value: "",
            },
            {
                label: "Crafting",
                value: "crafting",
            },
            {
                label: "Hell Forged",
                value: "hell-forged",
            },
            {
                label: "Purgatory Chains",
                value: "purgatory-chains",
            },
            {
                label: "Pirate Lord Leather",
                value: "pirate-lord-leather",
            },
            {
                label: "Corrupted Ice",
                value: "corrupted-ice",
            },
            {
                label: "Twisted Earth",
                value: "twisted-earth",
            },
            {
                label: "Delusional Silver",
                value: "delusional-silver",
            },
        ];
    }

    setFileForUpload(event: React.ChangeEvent<HTMLInputElement>) {
        if (event.target.files !== null) {
            this.setState(
                {
                    image_to_upload: event.target.files[0],
                },
                () => {
                    this.updateParentElement();
                },
            );
        }
    }

    defaultSelectedAction() {
        if (this.state.selected_live_wire_component !== null) {
            return this.buildOptions().filter(
                (option: any) =>
                    option.value === this.state.selected_live_wire_component,
            );
        }

        return [
            {
                label: "Please Select",
                value: "",
            },
        ];
    }

    defaultSelectedItemType() {
        if (this.state.selected_item_table_type !== null) {
            return this.buildItemTableTypes().filter(
                (option: any) =>
                    option.value === this.state.selected_item_table_type,
            );
        }

        return [
            {
                label: "Please Select",
                value: "",
            },
        ];
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        // @ts-ignore
        const apiKey = import.meta.env.VITE_TINY_MCE_API_KEY;

        return (
            <BasicCard additionalClasses={"mb-4"}>
                {this.props.index !== 0 ? (
                    <div className="mb-5">
                        <button
                            type="button"
                            onClick={this.removeSection.bind(this)}
                            className="text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]"
                        >
                            <i className="fas fa-times-circle"></i>
                        </button>
                    </div>
                ) : null}

                <Editor
                    apiKey={apiKey}
                    init={{
                        plugins: "lists link image paste help wordcount",
                        toolbar:
                            "undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | help",
                    }}
                    initialValue={this.state.content}
                    onEditorChange={this.setValue}
                />

                <div className="my-5">
                    <label className="label block mb-2">Order</label>
                    <input
                        type="number"
                        className="form-control"
                        onChange={this.setOrder.bind(this)}
                        value={this.state.order}
                    />
                </div>

                <div className="my-5">
                    <input
                        type="file"
                        className="form-control"
                        onChange={this.setFileForUpload.bind(this)}
                    />
                </div>

                <Select
                    onChange={this.setLivewireComponent.bind(this)}
                    options={this.buildOptions()}
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
                    value={this.defaultSelectedAction()}
                />

                <div className="my-4">
                    <Select
                        onChange={this.setItemTableType.bind(this)}
                        options={this.buildItemTableTypes()}
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
                        value={this.defaultSelectedItemType()}
                    />
                </div>

                <div className="flex mt-4 justify-end">
                    {this.props.sections_length !== 1 &&
                    this.props.add_section === null ? (
                        <div className="float-right">
                            <OrangeButton
                                button_label={"Update Section"}
                                on_click={() =>
                                    this.props.update_section(this.props.index)
                                }
                                additional_css={"mr-4"}
                            />
                        </div>
                    ) : null}

                    {this.props.sections_length === 1 &&
                    this.props.index === 0 ? (
                        <div className="float-right">
                            <SuccessButton
                                button_label={"Save and Finish"}
                                on_click={this.props.save_and_finish}
                                additional_css={"mr-4"}
                            />
                        </div>
                    ) : null}

                    {this.props.index !== 0 &&
                    this.props.add_section !== null ? (
                        <div className="float-right">
                            <SuccessButton
                                button_label={"Save and Finish"}
                                on_click={this.props.save_and_finish}
                                additional_css={"mr-4"}
                            />
                        </div>
                    ) : null}

                    {this.props.add_section !== null ? (
                        <div className="float-right">
                            <PrimaryButton
                                button_label={"Add Section"}
                                on_click={this.props.add_section}
                                additional_css={"mr-4"}
                            />
                        </div>
                    ) : null}
                </div>
            </BasicCard>
        );
    }
}
