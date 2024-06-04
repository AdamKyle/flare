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
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import Skills from "./tabs/skill-tabs/skills";
import KingdomPassives from "./tabs/skill-tabs/kingdom-passives";
import CraftingSkills from "./tabs/skill-tabs/crafting-skills";
import Ajax from "../../../lib/ajax/ajax";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import { watchForDarkModeSkillsChange } from "../../../lib/game/dark-mode-watcher";
var CharacterSkillsTabs = (function (_super) {
    __extends(CharacterSkillsTabs, _super);
    function CharacterSkillsTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "skills",
                name: "Skills",
            },
            {
                key: "crafting",
                name: "Crafting Skills",
            },
            {
                key: "kingdom-passives",
                name: "Kingdom Passives",
            },
        ];
        _this.state = {
            loading: true,
            dark_tables: false,
            skills: null,
        };
        _this.updateCharacterSkills = Echo.private(
            "update-skill-" + _this.props.user_id,
        );
        return _this;
    }
    CharacterSkillsTabs.prototype.componentDidMount = function () {
        var _this = this;
        watchForDarkModeSkillsChange(this);
        if (this.props.finished_loading) {
            new Ajax()
                .setRoute("character/skills/" + this.props.character_id)
                .doAjaxCall(
                    "get",
                    function (result) {
                        _this.setState({
                            skills: result.data,
                            loading: false,
                        });
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        }
        this.updateCharacterSkills.listen(
            "Game.Skills.Events.UpdateCharacterSkills",
            function (event) {
                var skills = JSON.parse(JSON.stringify(_this.state.skills));
                if (event.trainingSkills.length > 0) {
                    skills.training_skills = event.trainingSkills;
                }
                if (event.craftingSkills.length > 0) {
                    skills.crafting_skills = event.craftingSkills;
                }
                _this.setState({
                    skills: skills,
                });
            },
        );
    };
    CharacterSkillsTabs.prototype.updateSkills = function (skills) {
        if (typeof skills !== "undefined") {
            var stateSkills = JSON.parse(JSON.stringify(this.state.skills));
            var keys = Object.keys(skills);
            stateSkills[keys[0]] = skills[keys[0]];
            this.setState({
                skills: stateSkills,
            });
        }
    };
    CharacterSkillsTabs.prototype.render = function () {
        if (this.state.loading || this.state.skills === null) {
            return React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(ComponentLoading, null),
            );
        }
        return React.createElement(
            Tabs,
            { tabs: this.tabs, full_width: true },
            React.createElement(
                TabPanel,
                { key: "skills" },
                React.createElement(Skills, {
                    trainable_skills: this.state.skills.training_skills,
                    dark_table: this.state.dark_tables,
                    is_dead: this.props.is_dead,
                    update_skills: this.updateSkills.bind(this),
                    character_id: this.props.character_id,
                    is_automation_running: this.props.is_automation_running,
                }),
            ),
            React.createElement(
                TabPanel,
                { key: "crafting" },
                React.createElement(CraftingSkills, {
                    crafting_skills: this.state.skills.crafting_skills,
                    dark_table: this.state.dark_tables,
                }),
            ),
            React.createElement(
                TabPanel,
                { key: "kingdom-passives" },
                React.createElement(KingdomPassives, {
                    is_dead: this.props.is_dead,
                    character_id: this.props.character_id,
                    is_automation_running: this.props.is_automation_running,
                }),
            ),
        );
    };
    return CharacterSkillsTabs;
})(React.Component);
export default CharacterSkillsTabs;
//# sourceMappingURL=character-skills-tabs.js.map
