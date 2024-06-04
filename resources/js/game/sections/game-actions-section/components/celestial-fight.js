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
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import ServerFight from "./fight-section/server-fight";
import BattleMesages from "./fight-section/battle-mesages";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
var CelestialFight = (function (_super) {
    __extends(CelestialFight, _super);
    function CelestialFight(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            monster_health: 0,
            character_health: 0,
            monster_max_health: 0,
            character_max_health: 0,
            monster_name: "",
            battle_messages: [],
            loading: true,
            preforming_action: false,
            error_message: null,
        };
        _this.celestialFight = Echo.join("celestial-fight-changes");
        return _this;
    }
    CelestialFight.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "celestial-fight/" +
                    this.props.character.id +
                    "/" +
                    this.props.celestial_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        monster_health:
                            result.data.fight.monster.current_health,
                        character_health:
                            result.data.fight.character.current_health,
                        monster_max_health:
                            result.data.fight.monster.max_health,
                        character_max_health:
                            result.data.fight.character.max_health,
                        monster_name: result.data.fight.monster_name,
                        loading: false,
                    });
                },
                function (error) {
                    _this.setState({ loading: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
        this.celestialFight.listen(
            "Game.Battle.Events.UpdateCelestialFight",
            function (event) {
                _this.props.update_celestial(0);
                _this.setState({
                    monster_health: event.data.celestial_fight_over
                        ? 0
                        : event.data.monster_current_health,
                    battle_messages: [event.data.who_killed],
                });
            },
        );
    };
    CelestialFight.prototype.attack = function (type) {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "attack-celestial/" +
                            _this.props.character.id +
                            "/" +
                            _this.props.celestial_id,
                    )
                    .setParameters({
                        attack_type: type,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                preforming_action: false,
                                battle_messages: result.data.logs,
                                character_health:
                                    result.data.health.current_character_health,
                                monster_health:
                                    result.data.health.current_monster_health,
                            });
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    CelestialFight.prototype.revive = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute("celestial-revive/" + _this.props.character.id)
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                monster_health:
                                    result.data.fight.monster.current_health,
                                character_health:
                                    result.data.fight.character.current_health,
                                monster_max_health:
                                    result.data.fight.monster.max_health,
                                character_max_health:
                                    result.data.fight.character.max_health,
                                preforming_action: false,
                            });
                        },
                        function (error) {
                            console.error(error);
                        },
                    );
            },
        );
    };
    CelestialFight.prototype.render = function () {
        return this.state.loading
            ? React.createElement(ComponentLoading, null)
            : this.state.error_message === null
              ? React.createElement(
                    ServerFight,
                    {
                        monster_health: this.state.monster_health,
                        character_health: this.state.character_health,
                        monster_max_health: this.state.monster_max_health,
                        character_max_health: this.state.character_max_health,
                        monster_name: this.state.monster_name,
                        preforming_action: this.state.preforming_action,
                        character_name: this.props.character.name,
                        is_dead: this.props.character.is_dead,
                        can_attack: this.props.character.can_attack,
                        monster_id: this.props.celestial_id,
                        attack: this.attack.bind(this),
                        manage_server_fight: this.props.manage_celestial_fight,
                        revive: this.revive.bind(this),
                    },
                    React.createElement(BattleMesages, {
                        is_small: this.props.is_small,
                        battle_messages: this.state.battle_messages,
                    }),
                )
              : React.createElement(
                    DangerAlert,
                    { additional_css: "my-4" },
                    this.state.error_message,
                );
    };
    return CelestialFight;
})(React.Component);
export default CelestialFight;
//# sourceMappingURL=celestial-fight.js.map
