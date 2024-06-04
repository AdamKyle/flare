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
import React, { Fragment } from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import clsx from "clsx";
import { upperFirst } from "lodash";
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
var SkillInformation = (function (_super) {
    __extends(SkillInformation, _super);
    function SkillInformation(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            skill_data: {},
        };
        return _this;
    }
    SkillInformation.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "character/skill/" +
                    this.props.skill.character_id +
                    "/" +
                    this.props.skill.id,
            )
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        loading: false,
                        skill_data: response.data,
                    });
                },
                function (error) {},
            );
    };
    SkillInformation.prototype.getFilteredFields = function () {
        var _this = this;
        var validFields = [
            "unit_time_reduction",
            "building_time_reduction",
            "unit_movement_time_reduction",
            "base_damage_mod",
            "base_healing_mod",
            "base_ac_mod",
            "fight_timeout_mod",
            "move_timeout_mod",
            "class_bonus",
        ];
        return validFields.filter(function (field) {
            return _this.state.skill_data[field] > 0.0;
        });
    };
    SkillInformation.prototype.iSkillDetailsEmpty = function () {
        return !(this.getFilteredFields().length > 0);
    };
    SkillInformation.prototype.renderDetails = function () {
        var _this = this;
        return this.getFilteredFields().map(function (attributeName) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    upperFirst(attributeName.replaceAll("_", " ")),
                ),
                React.createElement(
                    "dd",
                    null,
                    (_this.state.skill_data[attributeName] * 100).toFixed(2),
                    "%",
                ),
            );
        });
    };
    SkillInformation.prototype.renderSkillDetails = function () {
        return React.createElement("dl", null, this.renderDetails());
    };
    SkillInformation.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.state.skill_data.name,
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "p-4 m-4" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      this.state.skill_data.is_locked
                          ? React.createElement(
                                DangerAlert,
                                { additional_css: "mb-4 mt-4" },
                                "This skill is locked. You will need to complete a quest to unlock it.",
                            )
                          : null,
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          this.state.skill_data.description,
                      ),
                      React.createElement(
                          "div",
                          {
                              className: clsx({
                                  "grid gap-2 md:grid-cols-2 md:gap-4":
                                      !this.iSkillDetailsEmpty(),
                              }),
                          },
                          React.createElement(
                              "div",
                              null,
                              React.createElement(
                                  "dl",
                                  null,
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Current Level",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.state.skill_data.level,
                                  ),
                                  React.createElement("dt", null, "Max Level"),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.state.skill_data.max_level,
                                  ),
                                  React.createElement("dt", null, "XP Towards"),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.state.skill_data.xp_towards !== null
                                          ? (
                                                this.state.skill_data
                                                    .xp_towards * 100
                                            ).toFixed(2)
                                          : 0.0,
                                      "%",
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Skill Bonus",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      (
                                          this.state.skill_data.skill_bonus *
                                          100
                                      ).toFixed(2),
                                      "%",
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Skill XP Bonus",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      (
                                          this.state.skill_data.skill_xp_bonus *
                                          100
                                      ).toFixed(2),
                                      " ",
                                      "%",
                                  ),
                                  React.createElement("dt", null, "XP"),
                                  React.createElement(
                                      "dd",
                                      null,
                                      formatNumber(this.state.skill_data.xp),
                                      " ",
                                      "/",
                                      " ",
                                      formatNumber(
                                          this.state.skill_data.xp_max,
                                      ),
                                  ),
                              ),
                          ),
                          !this.iSkillDetailsEmpty()
                              ? React.createElement(
                                    "div",
                                    null,
                                    this.renderSkillDetails(),
                                )
                              : null,
                      ),
                  ),
        );
    };
    return SkillInformation;
})(React.Component);
export default SkillInformation;
//# sourceMappingURL=skill-information.js.map
