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
var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
var __metadata =
    (this && this.__metadata) ||
    function (k, v) {
        if (
            typeof Reflect === "object" &&
            typeof Reflect.metadata === "function"
        )
            return Reflect.metadata(k, v);
    };
var __param =
    (this && this.__param) ||
    function (paramIndex, decorator) {
        return function (target, key) {
            decorator(target, key, paramIndex);
        };
    };
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../core-event-listener";
var CharacterListeners = (function () {
    function CharacterListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    CharacterListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    CharacterListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.characterTopBar = echo.private(
                "update-top-bar-" + this.userId,
            );
            this.characterCurrencies = echo.private(
                "update-currencies-" + this.userId,
            );
            this.characterAttacks = echo.private(
                "update-character-attacks-" + this.userId,
            );
            this.characterStatus = echo.private(
                "update-character-status-" + this.userId,
            );
            this.characterRevive = echo.private(
                "character-revive-" + this.userId,
            );
            this.characterAttackData = echo.private(
                "update-character-attack-" + this.userId,
            );
            this.globalTimeOut = echo.private("global-timeout-" + this.userId);
        } catch (e) {
            throw new Error(e);
        }
    };
    CharacterListeners.prototype.listen = function () {
        this.listenToCharacterTopBar();
        this.listenToCurrencyUpdates();
        this.listenToAttackDataUpdates();
        this.listenForCharacterStatusUpdates();
        this.listenForCharacterRevive();
        this.listenForCharacterAttackDataUpdates();
        this.listenForGlobalUpdatesThatAffectTheCharacter();
    };
    CharacterListeners.prototype.listenToCharacterTopBar = function () {
        var _this = this;
        if (!this.characterTopBar) {
            return;
        }
        this.characterTopBar.listen(
            "Game.Core.Events.UpdateTopBarBroadcastEvent",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState(
                    {
                        character: __assign(
                            __assign({}, _this.component.state.character),
                            event.characterSheet,
                        ),
                    },
                    function () {
                        if (event.characterSheet.is_banned) {
                            location.reload();
                        }
                    },
                );
            },
        );
    };
    CharacterListeners.prototype.listenToCurrencyUpdates = function () {
        var _this = this;
        if (!this.characterCurrencies) {
            return;
        }
        this.characterCurrencies.listen(
            "Game.Core.Events.UpdateCharacterCurrenciesBroadcastEvent",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    character: __assign(
                        __assign({}, _this.component.state.character),
                        event.currencies,
                    ),
                });
            },
        );
    };
    CharacterListeners.prototype.listenToAttackDataUpdates = function () {
        var _this = this;
        if (!this.characterAttacks) {
            return;
        }
        this.characterAttacks.listen(
            "Game.Core.Events.UpdateCharacterAttacks",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    character: __assign(
                        __assign({}, _this.component.state.character),
                        event.characterAttacks,
                    ),
                });
            },
        );
    };
    CharacterListeners.prototype.listenForCharacterStatusUpdates = function () {
        var _this = this;
        if (!this.characterStatus) {
            return;
        }
        this.characterStatus.listen(
            "Game.Battle.Events.UpdateCharacterStatus",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    character_status: event.characterStatuses,
                    character: __assign(
                        __assign({}, _this.component.state.character),
                        event.characterStatuses,
                    ),
                });
            },
        );
    };
    CharacterListeners.prototype.listenForCharacterRevive = function () {
        var _this = this;
        if (!this.characterRevive) {
            return;
        }
        this.characterRevive.listen(
            "Game.Battle.Events.CharacterRevive",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.health = event.health;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    CharacterListeners.prototype.listenForCharacterAttackDataUpdates =
        function () {
            var _this = this;
            if (!this.characterAttackData) {
                return;
            }
            this.characterAttackData.listen(
                "Flare.Events.UpdateCharacterAttackBroadcastEvent",
                function (event) {
                    if (!_this.component) {
                        return;
                    }
                    _this.component.setState({
                        character: __assign(
                            __assign({}, _this.component.state.character),
                            event.attack,
                        ),
                    });
                },
            );
        };
    CharacterListeners.prototype.listenForGlobalUpdatesThatAffectTheCharacter =
        function () {
            var _this = this;
            if (!this.globalTimeOut) {
                return;
            }
            this.globalTimeOut.listen(
                "Game.Core.Events.GlobalTimeOut",
                function (event) {
                    if (!_this.component) {
                        return;
                    }
                    _this.component.setState({
                        show_global_timeout: event.showTimeOut,
                    });
                },
            );
        };
    CharacterListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        CharacterListeners,
    );
    return CharacterListeners;
})();
export default CharacterListeners;
//# sourceMappingURL=character-listeners.js.map
