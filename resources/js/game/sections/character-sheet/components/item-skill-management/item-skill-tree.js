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
import { Tree, TreeNode } from "react-organizational-chart";
import SkillTreeNode from "./skill-tree/skill-tree-node";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import {
    isSkillLocked,
    getSkillProgressionData,
} from "./helpers/is-skill-locked";
var ItemSkillTree = (function (_super) {
    __extends(ItemSkillTree, _super);
    function ItemSkillTree(props) {
        return _super.call(this, props) || this;
    }
    ItemSkillTree.prototype.buildNodes = function (skills) {
        var _this = this;
        var nodes;
        nodes = skills.children.map(function (child) {
            var progressionData = getSkillProgressionData(
                child,
                _this.props.progression_data,
            );
            if (typeof progressionData !== "undefined") {
                return React.createElement(
                    TreeNode,
                    {
                        label: React.createElement(SkillTreeNode, {
                            skill: child,
                            skill_progression: progressionData,
                            show_passive_modal:
                                _this.props.show_skill_management,
                            is_locked: isSkillLocked(
                                child,
                                _this.props.skill_data,
                                _this.props.progression_data,
                            ),
                        }),
                    },
                    _this.buildNodes(child),
                );
            }
        });
        return nodes.filter(function (item) {
            return typeof item !== "undefined";
        });
    };
    ItemSkillTree.prototype.render = function () {
        var progressionData = getSkillProgressionData(
            this.props.skill_data[0],
            this.props.progression_data,
        );
        if (typeof progressionData === "undefined") {
            return React.createElement(
                DangerAlert,
                null,
                "Could not render item skill tree, something is wrong. The First skill does not have any progression data.",
            );
        }
        return React.createElement(
            "div",
            {
                className:
                    "overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]",
            },
            React.createElement(
                Tree,
                {
                    lineWidth: "2px",
                    lineColor: "green",
                    lineBorderRadius: "10px",
                    label: React.createElement(SkillTreeNode, {
                        skill: this.props.skill_data[0],
                        skill_progression: progressionData,
                        show_passive_modal: this.props.show_skill_management,
                        is_locked: false,
                    }),
                },
                this.buildNodes(this.props.skill_data[0]),
            ),
        );
    };
    return ItemSkillTree;
})(React.Component);
export default ItemSkillTree;
//# sourceMappingURL=item-skill-tree.js.map
