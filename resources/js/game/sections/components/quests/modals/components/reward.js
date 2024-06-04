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
import { formatNumber } from "../../../../../lib/game/format-number";
import { kebabCase } from "lodash";
var Reward = (function (_super) {
    __extends(Reward, _super);
    function Reward(props) {
        return _super.call(this, props) || this;
    }
    Reward.prototype.getFeatureLink = function () {
        return React.createElement(
            "a",
            {
                href:
                    "/information/" +
                    kebabCase(
                        this.props.quest.feature_to_unlock_name.toLowerCase(),
                    ),
                target: "_blank",
            },
            this.props.quest.feature_to_unlock_name,
            " ",
            React.createElement("i", { className: "fas fa-external-link-alt" }),
        );
    };
    Reward.prototype.render = function () {
        return React.createElement(
            "dl",
            null,
            this.props.quest.reward_xp !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "XP Reward"),
                      React.createElement(
                          "dd",
                          null,
                          formatNumber(this.props.quest.reward_xp),
                      ),
                  )
                : null,
            this.props.quest.reward_gold !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Gold Reward"),
                      React.createElement(
                          "dd",
                          null,
                          formatNumber(this.props.quest.reward_gold),
                      ),
                  )
                : null,
            this.props.quest.reward_gold_dust !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Gold Dust Reward"),
                      React.createElement(
                          "dd",
                          null,
                          formatNumber(this.props.quest.reward_gold_dust),
                      ),
                  )
                : null,
            this.props.quest.reward_shards !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Shards Reward"),
                      React.createElement(
                          "dd",
                          null,
                          formatNumber(this.props.quest.reward_shards),
                      ),
                  )
                : null,
            this.props.quest.unlocks_skill
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Unlocks New Skill"),
                      React.createElement(
                          "dd",
                          null,
                          this.props.quest.unlocks_skill_name,
                      ),
                  )
                : null,
            this.props.quest.feature_to_unlock_name !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Unlocks Game Feature"),
                      React.createElement("dd", null, this.getFeatureLink()),
                  )
                : null,
            this.props.quest.unlocks_passive_name !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "dt",
                          null,
                          "Unlocks Kingdom Passive",
                      ),
                      React.createElement(
                          "dd",
                          null,
                          this.props.quest.unlocks_passive_name,
                      ),
                  )
                : null,
            this.props.quest.reward_item !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("dt", null, "Item reward"),
                      React.createElement(
                          "dd",
                          null,
                          React.createElement(
                              "a",
                              {
                                  href:
                                      "/items/" +
                                      this.props.quest.reward_item.id,
                                  target: "_blank",
                              },
                              this.props.quest.reward_item.name,
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                      ),
                  )
                : null,
        );
    };
    return Reward;
})(React.Component);
export default Reward;
//# sourceMappingURL=reward.js.map
