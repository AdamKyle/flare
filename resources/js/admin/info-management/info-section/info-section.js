var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import Select from "react-select";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import { isEqual } from "lodash";
import ComponentLoading from "../../../game/components/ui/loading/component-loading";
import SuccessButton from "../../../game/components/ui/buttons/success-button";
import OrangeButton from "../../../game/components/ui/buttons/orange-button";
import { Editor } from "@tinymce/tinymce-react";
var InfoSection = (function (_super) {
    __extends(InfoSection, _super);
    function InfoSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            content: "",
            selected_live_wire_component: null,
            selected_item_table_type: null,
            image_to_upload: null,
            order: "",
            loading: true,
        };
        return _this;
    }
    InfoSection.prototype.componentDidMount = function () {
        var self = this;
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
    };
    InfoSection.prototype.componentDidUpdate = function (prevProps) {
        if (!isEqual(this.props.content.content, prevProps.content.content)) {
            this.setState({
                content: this.props.content.content,
            });
        }
    };
    InfoSection.prototype.setValue = function (data) {
        var _this = this;
        this.setState(
            {
                content: data,
            },
            function () {
                _this.updateParentElement();
            },
        );
    };
    InfoSection.prototype.setLivewireComponent = function (data) {
        var _this = this;
        this.setState(
            {
                selected_live_wire_component:
                    data.value !== "" ? data.value : null,
            },
            function () {
                _this.updateParentElement();
            },
        );
    };
    InfoSection.prototype.setItemTableType = function (data) {
        var _this = this;
        this.setState(
            {
                selected_item_table_type: data.value !== "" ? data.value : null,
            },
            function () {
                _this.updateParentElement();
            },
        );
    };
    InfoSection.prototype.setOrder = function (e) {
        var _this = this;
        this.setState(
            {
                order: e.target.value,
            },
            function () {
                _this.updateParentElement();
            },
        );
    };
    InfoSection.prototype.updateParentElement = function () {
        this.props.update_parent_element(this.props.index, {
            live_wire_component: this.state.selected_live_wire_component,
            item_table_type: this.state.selected_item_table_type,
            content: this.state.content,
            content_image_path: this.state.image_to_upload,
            order: this.state.order,
        });
    };
    InfoSection.prototype.removeSection = function () {
        this.props.remove_section(this.props.index);
    };
    InfoSection.prototype.buildOptions = function () {
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
    };
    InfoSection.prototype.buildItemTableTypes = function () {
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
    };
    InfoSection.prototype.setFileForUpload = function (event) {
        var _this = this;
        if (event.target.files !== null) {
            this.setState(
                {
                    image_to_upload: event.target.files[0],
                },
                function () {
                    _this.updateParentElement();
                },
            );
        }
    };
    InfoSection.prototype.defaultSelectedAction = function () {
        var _this = this;
        if (this.state.selected_live_wire_component !== null) {
            return this.buildOptions().filter(function (option) {
                return (
                    option.value === _this.state.selected_live_wire_component
                );
            });
        }
        return [
            {
                label: "Please Select",
                value: "",
            },
        ];
    };
    InfoSection.prototype.defaultSelectedItemType = function () {
        var _this = this;
        if (this.state.selected_item_table_type !== null) {
            return this.buildItemTableTypes().filter(function (option) {
                return option.value === _this.state.selected_item_table_type;
            });
        }
        return [
            {
                label: "Please Select",
                value: "",
            },
        ];
    };
    InfoSection.prototype.render = function () {
        var _this = this;
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        var apiKey = import.meta.env.VITE_TINY_MCE_API_KEY;
        return React.createElement(
            BasicCard,
            { additionalClasses: "mb-4" },
            this.props.index !== 0
                ? React.createElement(
                      "div",
                      { className: "mb-5" },
                      React.createElement(
                          "button",
                          {
                              type: "button",
                              onClick: this.removeSection.bind(this),
                              className:
                                  "text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]",
                          },
                          React.createElement("i", {
                              className: "fas fa-times-circle",
                          }),
                      ),
                  )
                : null,
            React.createElement(Editor, {
                apiKey: apiKey,
                init: {
                    plugins: "lists link image paste help wordcount",
                    toolbar:
                        "undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | help",
                },
                initialValue: this.state.content,
                onEditorChange: this.setValue.bind(this),
            }),
            React.createElement(
                "div",
                { className: "my-5" },
                React.createElement(
                    "label",
                    { className: "label block mb-2" },
                    "Order",
                ),
                React.createElement("input", {
                    type: "number",
                    className: "form-control",
                    onChange: this.setOrder.bind(this),
                    value: this.state.order,
                }),
            ),
            React.createElement(
                "div",
                { className: "my-5" },
                React.createElement("input", {
                    type: "file",
                    className: "form-control",
                    onChange: this.setFileForUpload.bind(this),
                }),
            ),
            React.createElement(Select, {
                onChange: this.setLivewireComponent.bind(this),
                options: this.buildOptions(),
                menuPosition: "absolute",
                menuPlacement: "bottom",
                styles: {
                    menuPortal: function (base) {
                        return __assign(__assign({}, base), {
                            zIndex: 9999,
                            color: "#000000",
                        });
                    },
                },
                menuPortalTarget: document.body,
                value: this.defaultSelectedAction(),
            }),
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(Select, {
                    onChange: this.setItemTableType.bind(this),
                    options: this.buildItemTableTypes(),
                    menuPosition: "absolute",
                    menuPlacement: "bottom",
                    styles: {
                        menuPortal: function (base) {
                            return __assign(__assign({}, base), {
                                zIndex: 9999,
                                color: "#000000",
                            });
                        },
                    },
                    menuPortalTarget: document.body,
                    value: this.defaultSelectedItemType(),
                }),
            ),
            React.createElement(
                "div",
                { className: "flex mt-4 justify-end" },
                this.props.sections_length !== 1 &&
                    this.props.add_section === null
                    ? React.createElement(
                          "div",
                          { className: "float-right" },
                          React.createElement(OrangeButton, {
                              button_label: "Update Section",
                              on_click: function () {
                                  return _this.props.update_section(
                                      _this.props.index,
                                  );
                              },
                              additional_css: "mr-4",
                          }),
                      )
                    : null,
                this.props.sections_length === 1 && this.props.index === 0
                    ? React.createElement(
                          "div",
                          { className: "float-right" },
                          React.createElement(SuccessButton, {
                              button_label: "Save and Finish",
                              on_click: this.props.save_and_finish,
                              additional_css: "mr-4",
                          }),
                      )
                    : null,
                this.props.index !== 0 && this.props.add_section !== null
                    ? React.createElement(
                          "div",
                          { className: "float-right" },
                          React.createElement(SuccessButton, {
                              button_label: "Save and Finish",
                              on_click: this.props.save_and_finish,
                              additional_css: "mr-4",
                          }),
                      )
                    : null,
                this.props.add_section !== null
                    ? React.createElement(
                          "div",
                          { className: "float-right" },
                          React.createElement(PrimaryButton, {
                              button_label: "Add Section",
                              on_click: this.props.add_section,
                              additional_css: "mr-4",
                          }),
                      )
                    : null,
            ),
        );
    };
    return InfoSection;
})(React.Component);
export default InfoSection;
//# sourceMappingURL=info-section.js.map
