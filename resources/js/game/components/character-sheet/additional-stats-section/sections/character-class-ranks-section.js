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
import React from "react";
import CharacterClassRanks from "../../../../sections/character-sheet/components/character-class-ranks";
import CharacterClassRankSpecialtiesSection from "./character-class-rank-specialties-section";
import DropDown from "../../../ui/drop-down/drop-down";
import clsx from "clsx";
var CharacterClassRanksSection = (function (_super) {
    __extends(CharacterClassRanksSection, _super);
    function CharacterClassRanksSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            class_rank_type_to_show: "",
            class_special_type_to_show: "",
        };
        return _this;
    }
    CharacterClassRanksSection.prototype.setFilterTypeForClassRanks = function (
        type,
    ) {
        this.setState({
            class_rank_type_to_show: type,
        });
    };
    CharacterClassRanksSection.prototype.setFilterTypeForClassRankSpecialties =
        function (type) {
            this.setState({
                class_special_type_to_show: type,
            });
        };
    CharacterClassRanksSection.prototype.createTypeFilterDropDownForClassRanks =
        function () {
            var _this = this;
            return [
                {
                    name: "Class Ranks",
                    icon_class: "ra ra-player-pyromaniac",
                    on_click: function () {
                        return _this.setFilterTypeForClassRanks("class-ranks");
                    },
                },
                {
                    name: "Class Masteries",
                    icon_class: "ra ra-player-lift",
                    on_click: function () {
                        return _this.setFilterTypeForClassRanks(
                            "class-masteries",
                        );
                    },
                },
            ];
        };
    CharacterClassRanksSection.prototype.createTypeFilterForDropDownForClassMasteries =
        function () {
            var _this = this;
            return [
                {
                    name: "Class Specialties",
                    icon_class: "ra ra-player-pyromaniac",
                    on_click: function () {
                        return _this.setFilterTypeForClassRankSpecialties(
                            "class-specialties",
                        );
                    },
                },
                {
                    name: "Equipped Specials",
                    icon_class: "ra ra-player-lift",
                    on_click: function () {
                        return _this.setFilterTypeForClassRankSpecialties(
                            "equipped-specials",
                        );
                    },
                },
                {
                    name: "Your Other Specials",
                    icon_class: "ra ra-player-lift",
                    on_click: function () {
                        return _this.setFilterTypeForClassRankSpecialties(
                            "other-specialties",
                        );
                    },
                },
            ];
        };
    CharacterClassRanksSection.prototype.renderSelectedType = function () {
        switch (this.state.class_rank_type_to_show) {
            case "class-ranks":
                return React.createElement(CharacterClassRanks, {
                    character: this.props.character,
                });
            case "class-masteries":
                return React.createElement(
                    CharacterClassRankSpecialtiesSection,
                    {
                        view_port: 0,
                        is_open: true,
                        manage_modal: function () {},
                        title: "",
                        character: this.props.character,
                        finished_loading: true,
                        selected_type: this.state.class_special_type_to_show,
                    },
                );
            default:
                return React.createElement(CharacterClassRanks, {
                    character: this.props.character,
                });
        }
    };
    CharacterClassRanksSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "flex flex-row flex-wrap" },
                React.createElement(
                    "div",
                    { className: "my-4 max-w-full md:max-w-[25%]" },
                    React.createElement(DropDown, {
                        menu_items:
                            this.createTypeFilterDropDownForClassRanks(),
                        button_title: "Class Rank Type",
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className: clsx("my-4 max-w-full md:max-w-[25%] ml-4", {
                            hidden:
                                this.state.class_rank_type_to_show !==
                                "class-masteries",
                        }),
                    },
                    React.createElement(DropDown, {
                        menu_items:
                            this.createTypeFilterForDropDownForClassMasteries(),
                        button_title: "Class Masteries",
                    }),
                ),
            ),
            this.renderSelectedType(),
        );
    };
    return CharacterClassRanksSection;
})(React.Component);
export default CharacterClassRanksSection;
//# sourceMappingURL=character-class-ranks-section.js.map
