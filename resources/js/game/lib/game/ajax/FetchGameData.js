var __awaiter =
    (this && this.__awaiter) ||
    function (thisArg, _arguments, P, generator) {
        function adopt(value) {
            return value instanceof P
                ? value
                : new P(function (resolve) {
                      resolve(value);
                  });
        }
        return new (P || (P = Promise))(function (resolve, reject) {
            function fulfilled(value) {
                try {
                    step(generator.next(value));
                } catch (e) {
                    reject(e);
                }
            }
            function rejected(value) {
                try {
                    step(generator["throw"](value));
                } catch (e) {
                    reject(e);
                }
            }
            function step(result) {
                result.done
                    ? resolve(result.value)
                    : adopt(result.value).then(fulfilled, rejected);
            }
            step(
                (generator = generator.apply(thisArg, _arguments || [])).next(),
            );
        });
    };
var __generator =
    (this && this.__generator) ||
    function (thisArg, body) {
        var _ = {
                label: 0,
                sent: function () {
                    if (t[0] & 1) throw t[1];
                    return t[1];
                },
                trys: [],
                ops: [],
            },
            f,
            y,
            t,
            g;
        return (
            (g = { next: verb(0), throw: verb(1), return: verb(2) }),
            typeof Symbol === "function" &&
                (g[Symbol.iterator] = function () {
                    return this;
                }),
            g
        );
        function verb(n) {
            return function (v) {
                return step([n, v]);
            };
        }
        function step(op) {
            if (f) throw new TypeError("Generator is already executing.");
            while ((g && ((g = 0), op[0] && (_ = 0)), _))
                try {
                    if (
                        ((f = 1),
                        y &&
                            (t =
                                op[0] & 2
                                    ? y["return"]
                                    : op[0]
                                      ? y["throw"] ||
                                        ((t = y["return"]) && t.call(y), 0)
                                      : y.next) &&
                            !(t = t.call(y, op[1])).done)
                    )
                        return t;
                    if (((y = 0), t)) op = [op[0] & 2, t.value];
                    switch (op[0]) {
                        case 0:
                        case 1:
                            t = op;
                            break;
                        case 4:
                            _.label++;
                            return { value: op[1], done: false };
                        case 5:
                            _.label++;
                            y = op[1];
                            op = [0];
                            continue;
                        case 7:
                            op = _.ops.pop();
                            _.trys.pop();
                            continue;
                        default:
                            if (
                                !((t = _.trys),
                                (t = t.length > 0 && t[t.length - 1])) &&
                                (op[0] === 6 || op[0] === 2)
                            ) {
                                _ = 0;
                                continue;
                            }
                            if (
                                op[0] === 3 &&
                                (!t || (op[1] > t[0] && op[1] < t[3]))
                            ) {
                                _.label = op[1];
                                break;
                            }
                            if (op[0] === 6 && _.label < t[1]) {
                                _.label = t[1];
                                t = op;
                                break;
                            }
                            if (t && _.label < t[2]) {
                                _.label = t[2];
                                _.ops.push(op);
                                break;
                            }
                            if (t[2]) _.ops.pop();
                            _.trys.pop();
                            continue;
                    }
                    op = body.call(thisArg, _);
                } catch (e) {
                    op = [6, e];
                    y = 0;
                } finally {
                    f = t = 0;
                }
            if (op[0] & 5) throw op[1];
            return { value: op[0] ? op[1] : void 0, done: true };
        }
    };
import Ajax from "../../ajax/ajax";
import MapStateManager from "../../../sections/map/lib/state/map-state-manager";
var FetchGameData = (function () {
    function FetchGameData(component) {
        this.component = component;
        this.characterSheet = null;
        this.urls = [];
    }
    FetchGameData.prototype.setUrls = function (urls) {
        this.urls = urls;
        return this;
    };
    FetchGameData.prototype.doAjaxCalls = function () {
        return __awaiter(this, void 0, void 0, function () {
            var makeSequentialAjaxCalls;
            var _this = this;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (typeof this.urls === "undefined") {
                            return [2];
                        }
                        makeSequentialAjaxCalls = function (urls) {
                            return __awaiter(
                                _this,
                                void 0,
                                void 0,
                                function () {
                                    var url, result;
                                    return __generator(this, function (_a) {
                                        switch (_a.label) {
                                            case 0:
                                                if (urls.length === 0) {
                                                    return [2];
                                                }
                                                url = urls[0];
                                                return [
                                                    4,
                                                    this.makeAjaxCall(url.url),
                                                ];
                                            case 1:
                                                result = _a.sent();
                                                switch (url.name) {
                                                    case "character-sheet":
                                                        this.setCharacterSheet(
                                                            result,
                                                        );
                                                        break;
                                                    case "actions":
                                                        this.setActionData(
                                                            result,
                                                        );
                                                        break;
                                                    case "game-map":
                                                        this.setMapData(result);
                                                        break;
                                                    case "quests":
                                                        this.setQuestData(
                                                            result,
                                                        );
                                                        break;
                                                    case "kingdoms":
                                                        this.setKingdomsData(
                                                            result,
                                                        );
                                                        break;
                                                    default:
                                                        break;
                                                }
                                                return [
                                                    4,
                                                    makeSequentialAjaxCalls(
                                                        urls.slice(1),
                                                    ),
                                                ];
                                            case 2:
                                                _a.sent();
                                                return [2];
                                        }
                                    });
                                },
                            );
                        };
                        return [4, makeSequentialAjaxCalls(this.urls)];
                    case 1:
                        _a.sent();
                        return [2];
                }
            });
        });
    };
    FetchGameData.prototype.makeAjaxCall = function (url) {
        return __awaiter(this, void 0, Promise, function () {
            return __generator(this, function (_a) {
                return [
                    2,
                    new Promise(function (resolve, reject) {
                        new Ajax().setRoute(url).doAjaxCall(
                            "get",
                            function (result) {
                                resolve(result);
                            },
                            function (error) {
                                reject(error);
                            },
                        );
                    }),
                ];
            });
        });
    };
    FetchGameData.prototype.setCharacterSheet = function (result) {
        var _this = this;
        this.characterSheet = result.data.sheet;
        this.component.setState(
            {
                character: result.data.sheet,
                percentage_loaded: 0.2,
                secondary_loading_title: "Fetching Quest Data ...",
                character_currencies: {
                    gold: result.data.sheet.gold,
                    gold_dust: result.data.sheet.gold_dust,
                    shards: result.data.sheet.shards,
                    copper_coins: result.data.sheet.copper_coins,
                },
                character_status: {
                    can_attack: result.data.sheet.can_attack,
                    can_attack_again_at: result.data.sheet.can_attack_again_at,
                    can_craft: result.data.sheet.can_craft,
                    can_craft_again_at: result.data.sheet.can_craft_again_at,
                    is_dead: result.data.sheetis_dead,
                    automation_locked: result.data.sheet.automation_locked,
                    is_silenced: result.data.sheet.is_silenced,
                    killed_in_pvp: result.data.sheet.kill_in_pvp,
                },
                fame_action_tasks: result.data.sheet.current_fame_tasks,
            },
            function () {
                _this.component.setCharacterPosition(
                    result.data.sheet.base_position,
                );
                if (result.data.sheet.is_in_timeout) {
                    new Ajax().initiateGlobalTimeOut();
                }
            },
        );
    };
    FetchGameData.prototype.setQuestData = function (result) {
        this.component.setState({
            quests: result.data,
            percentage_loaded: this.component.state.percentage_loaded + 0.2,
            secondary_loading_title: "Fetching Kingdom Data ...",
        });
    };
    FetchGameData.prototype.setKingdomsData = function (result) {
        this.component.setState({
            kingdoms: result.data.kingdoms,
            kingdom_logs: result.data.logs,
            loading: false,
            percentage_loaded: this.component.state.percentage_loaded + 0.2,
            secondary_loading_title: "Fetching Action Data ...",
        });
    };
    FetchGameData.prototype.setActionData = function (result) {
        if (this.characterSheet === null) {
            return;
        }
        this.component.setState({
            percentage_loaded: this.component.state.percentage_loaded + 0.2,
            secondary_loading_title: "Fetching Map Data ...",
            action_data: {
                raid_monsters: [],
                monsters: result.data.monsters,
            },
        });
    };
    FetchGameData.prototype.setMapData = function (result) {
        this.component.setState({
            map_data: MapStateManager.buildCoreState(
                result.data,
                this.component,
            ),
        });
    };
    return FetchGameData;
})();
export default FetchGameData;
//# sourceMappingURL=FetchGameData.js.map
