import { capitalize } from "lodash";
var CraftingSectionManager = (function () {
    function CraftingSectionManager(component) {
        this.component = component;
    }
    CraftingSectionManager.prototype.cannotCraft = function () {
        var props = this.component.props;
        return (
            props.crafting_time_out > 0 ||
            !props.character_status.can_craft ||
            props.character_status.is_dead
        );
    };
    CraftingSectionManager.prototype.getSelectedCraftingOption = function () {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }
        return "";
    };
    CraftingSectionManager.prototype.smallCraftingList = function () {
        var options = [
            {
                label: "Craft",
                value: "craft",
            },
            {
                label: "Enchant",
                value: "enchant",
            },
            {
                label: "Trinketry",
                value: "trinketry",
            },
            {
                label: "Gem Crafting",
                value: "gem-crafting",
            },
        ];
        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                label: "Alchemy",
                value: "alchemy",
            });
        }
        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    label: "Workbench",
                    value: "workbench",
                });
            } else {
                options.splice(2, 0, {
                    label: "Workbench",
                    value: "workbench",
                });
            }
        }
        if (this.component.props.character.can_access_labyrinth_oracle) {
            if (typeof options[3] !== "undefined") {
                options.splice(3, 0, {
                    label: "Labyrinth Oracle",
                    value: "labyrinth-oracle",
                });
            } else {
                options.splice(4, 0, {
                    label: "Labyrinth Oracle",
                    value: "labyrinth-oracle",
                });
            }
        }
        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    label: "Queen of hearts",
                    value: "queen",
                });
            } else {
                options.splice(2, 0, {
                    label: "Queen of hearts",
                    value: "queen",
                });
            }
        }
        return options;
    };
    CraftingSectionManager.prototype.setCraftingTypeForSmallerActionsList =
        function (data) {
            this.component.setState({
                crafting_type: data.value,
            });
        };
    CraftingSectionManager.prototype.getSelectedCraftingTypeForSmallerActionsList =
        function () {
            var _this = this;
            if (this.component.state.crafting_type === null) {
                return [
                    {
                        label: "Please select type",
                        value: "",
                    },
                ];
            }
            var options = this.smallCraftingList();
            var option = options.filter(function (option) {
                return option.value === _this.component.state.crafting_type;
            });
            return option;
        };
    CraftingSectionManager.prototype.buildCraftingList = function (handler) {
        var options = [
            {
                name: "Craft",
                icon_class: "ra ra-hammer",
                on_click: function () {
                    return handler("craft");
                },
            },
            {
                name: "Enchant",
                icon_class: "ra ra-burning-embers",
                on_click: function () {
                    return handler("enchant");
                },
            },
            {
                name: "Trinketry",
                icon_class: "ra ra-anvil",
                on_click: function () {
                    return handler("trinketry");
                },
            },
        ];
        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                name: "Alchemy",
                icon_class: "ra ra-potion",
                on_click: function () {
                    return handler("alchemy");
                },
            });
        }
        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: function () {
                        return handler("workbench");
                    },
                });
            } else {
                options.splice(2, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: function () {
                        return handler("workbench");
                    },
                });
            }
        }
        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra  ra-hearts",
                    on_click: function () {
                        return handler("queen");
                    },
                });
            } else {
                options.splice(2, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra ra-hearts",
                    on_click: function () {
                        return handler("queen");
                    },
                });
            }
        }
        return options;
    };
    return CraftingSectionManager;
})();
export default CraftingSectionManager;
//# sourceMappingURL=crafting-section-manager.js.map
