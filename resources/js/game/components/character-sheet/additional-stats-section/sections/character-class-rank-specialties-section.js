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
import React, { Fragment } from "react";
import { formatNumber } from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import PrimaryButton from "../../../ui/buttons/primary-button";
import { watchForDarkModeClassSpecialtyChange } from "../../../../lib/game/dark-mode-watcher";
import Table from "../../../ui/data-tables/table";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import { startCase } from "lodash";
import Select from "react-select";
import InfoAlert from "../../../ui/alerts/simple-alerts/info-alert";
var CharacterClassRankSpecialtiesSection = (function (_super) {
    __extends(CharacterClassRankSpecialtiesSection, _super);
    function CharacterClassRankSpecialtiesSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            equipping: false,
            equipping_special_id: null,
            class_specialties: [],
            class_specials_for_table: [],
            specialties_equipped: [],
            other_class_specialties: [],
            original_class_specialties: [],
            filtered_other_class_specialties: [],
            class_ranks: [],
            dark_tables: false,
            show_equipped: false,
            special_selected: null,
            equipped_special: null,
            success_message: null,
            error_message: null,
            selected_filter: null,
            other_selected_filter: null,
        };
        _this.tabs = [
            {
                key: "class-specialties",
                name: "Class Specialties",
            },
            {
                key: "equipped-specialities",
                name: "Equipped Specialties",
            },
            {
                key: "other-specialties",
                name: "Your other Specialties",
            },
        ];
        return _this;
    }
    CharacterClassRankSpecialtiesSection.prototype.componentDidMount =
        function () {
            var _this = this;
            watchForDarkModeClassSpecialtyChange(this);
            if (this.props.character === null) {
                return;
            }
            new Ajax()
                .setRoute(
                    "class-ranks/" + this.props.character.id + "/specials",
                )
                .doAjaxCall(
                    "get",
                    function (response) {
                        _this.setState(
                            {
                                loading: false,
                                class_specialties:
                                    response.data.class_specialties,
                                class_specials_for_table:
                                    response.data.class_specialties,
                                specialties_equipped:
                                    response.data.specials_equipped,
                                class_ranks: response.data.class_ranks,
                                other_class_specialties:
                                    response.data.other_class_specials,
                                original_class_specialties:
                                    response.data.other_class_specials,
                                selected_filter:
                                    _this.props.character === null
                                        ? null
                                        : _this.props.character.class,
                                other_selected_filter:
                                    _this.props.character === null
                                        ? null
                                        : _this.props.character.class,
                            },
                            function () {
                                _this.filterTable();
                                _this.filterOtherClassSpecialsTable();
                            },
                        );
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        };
    CharacterClassRankSpecialtiesSection.prototype.filterTableByClass =
        function (data) {
            var _this = this;
            if (data.value === "Please select") {
                this.setState(
                    {
                        class_specials_for_table: this.state.class_specialties,
                        selected_filter:
                            this.props.character === null
                                ? null
                                : this.props.character.class,
                    },
                    function () {
                        _this.filterTable();
                        _this.filterOtherClassSpecialsTable();
                    },
                );
                return;
            }
            this.setState(
                {
                    selected_filter: data.value,
                },
                function () {
                    _this.filterTable();
                },
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.filterOtherSpecialties =
        function (data) {
            var _this = this;
            if (data.value === "Please select") {
                this.setState({
                    filtered_other_class_specialties:
                        this.state.other_class_specialties,
                });
                return;
            }
            this.setState(
                {
                    other_selected_filter: data.value,
                },
                function () {
                    _this.filterOtherClassSpecialsTable();
                },
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.filterTable = function () {
        var _this = this;
        if (this.state.selected_filter === null) {
            return;
        }
        var classSpecialties = JSON.parse(
            JSON.stringify(this.state.class_specialties),
        );
        if (this.state.selected_filter === "Equippable") {
            var specials = classSpecialties.filter(function (special) {
                var ranks = _this.state.class_ranks.filter(function (rank) {
                    if (rank.game_class_id === special.game_class_id) {
                        return rank.level >= special.requires_class_rank_level;
                    }
                });
                if (ranks.length > 0) {
                    return special;
                }
            });
            this.setState({
                class_specials_for_table: specials,
            });
            return;
        }
        this.setState({
            class_specials_for_table: classSpecialties.filter(
                function (special) {
                    return special.class_name === _this.state.selected_filter;
                },
            ),
        });
    };
    CharacterClassRankSpecialtiesSection.prototype.filterOtherClassSpecialsTable =
        function () {
            var _this = this;
            if (this.state.other_class_specialties === null) {
                return;
            }
            var otherSpecials = JSON.parse(
                JSON.stringify(this.state.original_class_specialties),
            );
            if (otherSpecials.length === 0) {
                return;
            }
            this.setState({
                other_class_specialties: otherSpecials.filter(
                    function (special) {
                        return (
                            special.class_name ===
                            _this.state.other_selected_filter
                        );
                    },
                ),
            });
        };
    CharacterClassRankSpecialtiesSection.prototype.classOptions = function (
        addEquippable,
    ) {
        var classes = [
            "Please select",
            "Heretic",
            "Fighter",
            "Vampire",
            "Ranger",
            "Prophet",
            "Thief",
            "Blacksmith",
            "Arcane Alchemist",
            "Prisoner",
            "Alcoholic",
            "Merchant",
            "Gunslinger",
            "Dancer",
            "Cleric",
            "Book Binder",
        ];
        if (addEquippable) {
            classes.splice(1, 0, "Equippable");
        }
        return classes.map(function (className) {
            return {
                label: className,
                value: className,
            };
        });
    };
    CharacterClassRankSpecialtiesSection.prototype.unequipSpecial = function (
        specialId,
    ) {
        var _this = this;
        this.setState(
            {
                equipping: true,
                equipping_special_id: specialId,
                success_message: null,
                error_message: null,
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "unequip-specialty/" +
                            _this.props.character.id +
                            "/" +
                            specialId,
                    )
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState(
                                {
                                    equipping: false,
                                    equipping_special_id: null,
                                    class_specialties:
                                        response.data.class_specialties,
                                    class_specials_for_table:
                                        response.data.class_specialties,
                                    specialties_equipped:
                                        response.data.specials_equipped,
                                    class_ranks: response.data.class_ranks,
                                    other_class_specialties:
                                        response.data.other_class_specials,
                                    success_message: response.data.message,
                                },
                                function () {
                                    _this.filterTable();
                                    _this.filterOtherClassSpecialsTable();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ equipping: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    CharacterClassRankSpecialtiesSection.prototype.equipSpecial = function (
        specialId,
    ) {
        var _this = this;
        this.setState(
            {
                equipping: true,
                equipping_special_id: specialId,
                success_message: null,
                error_message: null,
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "equip-specialty/" +
                            _this.props.character.id +
                            "/" +
                            specialId,
                    )
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState(
                                {
                                    equipping: false,
                                    equipping_special_id: null,
                                    success_message: response.data.message,
                                    class_specialties:
                                        response.data.class_specialties,
                                    class_specials_for_table:
                                        response.data.class_specialties,
                                    specialties_equipped:
                                        response.data.specials_equipped,
                                    class_ranks: response.data.class_ranks,
                                    other_class_specialties:
                                        response.data.other_class_specials,
                                },
                                function () {
                                    _this.filterTable();
                                    _this.filterOtherClassSpecialsTable();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ equipping: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    CharacterClassRankSpecialtiesSection.prototype.doesSpecialtyDealDamage =
        function (specialty) {
            if (specialty.specialty_damage !== null) {
                return specialty.specialty_damage > 0;
            }
            return false;
        };
    CharacterClassRankSpecialtiesSection.prototype.classSpecialtiesTable =
        function () {
            var _this = this;
            return [
                {
                    name: "Name",
                    selector: function (row) {
                        return row.name;
                    },
                    cell: function (row) {
                        return React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "button",
                                {
                                    className:
                                        "hover:underline text-blue-500 dark:text-blue-400",
                                    onClick: function () {
                                        return _this.manageViewSpecialty(row);
                                    },
                                },
                                row.name,
                            ),
                        );
                    },
                },
                {
                    name: "Class Name",
                    selector: function (row) {
                        return row.class_name;
                    },
                },
                {
                    name: "Class Rank Required",
                    selector: function (row) {
                        return row.requires_class_rank_level;
                    },
                },
                {
                    name: "Deals Damage",
                    selector: function (row) {
                        return _this.doesSpecialtyDealDamage(row)
                            ? "Yes"
                            : "No";
                    },
                },
                {
                    name: "Actions",
                    selector: function (row) {
                        return row.id;
                    },
                    cell: function (row) {
                        return React.createElement(
                            Fragment,
                            null,
                            _this.specialtyIsEquipped(row.id)
                                ? React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-green-500 dark:text-green-400",
                                      },
                                      "Specialty is equipped",
                                  )
                                : _this.hasDamageSpecialtyEquipped(row)
                                  ? React.createElement(
                                        "span",
                                        {
                                            className:
                                                "text-red-500 dark:text-red-400",
                                        },
                                        "You already have a damage specialty equipped.",
                                    )
                                  : React.createElement(PrimaryButton, {
                                        button_label:
                                            _this.state.equipping &&
                                            _this.state.equipping_special_id ===
                                                row.id
                                                ? React.createElement(
                                                      Fragment,
                                                      null,
                                                      React.createElement("i", {
                                                          className:
                                                              "fas fa-spinner fa-spin",
                                                      }),
                                                      " ",
                                                      "Equip",
                                                  )
                                                : "Equip",
                                        on_click: function () {
                                            return _this.equipSpecial(row.id);
                                        },
                                        disabled: _this.isEquipButtonDisabled(
                                            row.requires_class_rank_level,
                                            row.game_class_id,
                                        ),
                                    }),
                        );
                    },
                },
            ];
        };
    CharacterClassRankSpecialtiesSection.prototype.specialtyIsEquipped =
        function (specialtyId) {
            return (
                this.state.specialties_equipped.filter(function (specialty) {
                    return specialtyId === specialty.game_class_special_id;
                }).length > 0
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.classSpecialtiesEquippedTable =
        function (equipSpecial) {
            var _this = this;
            return [
                {
                    name: "Name",
                    selector: function (row) {
                        return row.game_class_special.name;
                    },
                    cell: function (row) {
                        return React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "button",
                                {
                                    className:
                                        "hover:underline text-blue-500 dark:text-blue-400",
                                    onClick: function () {
                                        return _this.manageViewSpecialtyEquipped(
                                            row,
                                            equipSpecial ? false : true,
                                        );
                                    },
                                },
                                row.game_class_special.name,
                            ),
                        );
                    },
                },
                {
                    name: "Class Name",
                    selector: function (row) {
                        return row.class_name;
                    },
                },
                {
                    name: "Level",
                    selector: function (row) {
                        return row.level;
                    },
                },
                {
                    name: "XP",
                    selector: function (row) {
                        return row.id;
                    },
                    cell: function (row) {
                        return React.createElement(
                            Fragment,
                            null,
                            formatNumber(row.current_xp),
                            "/",
                            formatNumber(row.required_xp),
                        );
                    },
                },
                {
                    name: "Deals Damage",
                    selector: function (row) {
                        return _this.doesSpecialtyDealDamage(row)
                            ? "Yes"
                            : "No";
                    },
                },
                {
                    name: "Actions",
                    selector: function (row) {
                        return row.id;
                    },
                    cell: function (row) {
                        return React.createElement(
                            Fragment,
                            null,
                            equipSpecial
                                ? _this.specialtyIsEquipped(row.id)
                                    ? React.createElement(
                                          "span",
                                          {
                                              className:
                                                  "text-green-500 dark:text-green-400",
                                          },
                                          "Specialty is equipped",
                                      )
                                    : _this.hasDamageSpecialtyEquipped(row)
                                      ? React.createElement(
                                            "span",
                                            {
                                                className:
                                                    "text-red-500 dark:text-red-400",
                                            },
                                            "You already have a damage specialty equipped.",
                                        )
                                      : React.createElement(PrimaryButton, {
                                            button_label:
                                                _this.state.equipping &&
                                                _this.state
                                                    .equipping_special_id ===
                                                    row.game_class_special_id
                                                    ? React.createElement(
                                                          Fragment,
                                                          null,
                                                          React.createElement(
                                                              "i",
                                                              {
                                                                  className:
                                                                      "fas fa-spinner fa-spin",
                                                              },
                                                          ),
                                                          " ",
                                                          "Equip",
                                                      )
                                                    : "Equip",
                                            on_click: function () {
                                                return _this.equipSpecial(
                                                    row.game_class_special_id,
                                                );
                                            },
                                            disabled:
                                                _this.isEquipButtonDisabled(
                                                    row.game_class_special
                                                        .requires_class_rank_level,
                                                    row.game_class_special
                                                        .game_class_id,
                                                ),
                                        })
                                : React.createElement(PrimaryButton, {
                                      button_label:
                                          _this.state.equipping &&
                                          _this.state.equipping_special_id ===
                                              row.id
                                              ? React.createElement(
                                                    Fragment,
                                                    null,
                                                    React.createElement("i", {
                                                        className:
                                                            "fas fa-spinner fa-spin",
                                                    }),
                                                    " ",
                                                    "Unequip",
                                                )
                                              : "Unequip",
                                      on_click: function () {
                                          return _this.unequipSpecial(row.id);
                                      },
                                      disabled: _this.state.equipping,
                                  }),
                        );
                    },
                },
            ];
        };
    CharacterClassRankSpecialtiesSection.prototype.isEquipButtonDisabled =
        function (requiredLevel, classId) {
            if (this.state.equipping) {
                return true;
            }
            var rank = this.state.class_ranks.filter(function (rank) {
                return rank.game_class_id === classId;
            })[0];
            return rank.level < requiredLevel;
        };
    CharacterClassRankSpecialtiesSection.prototype.manageViewSpecialty =
        function (specialty) {
            this.setState({
                special_selected: specialty,
            });
        };
    CharacterClassRankSpecialtiesSection.prototype.manageViewSpecialtyEquipped =
        function (equippedSpecialty, showIsEquipped) {
            this.setState({
                equipped_special: equippedSpecialty,
                show_equipped: showIsEquipped,
            });
        };
    CharacterClassRankSpecialtiesSection.prototype.hasDamageSpecialtyEquipped =
        function (data) {
            return this.state.specialties_equipped.some(function (equipped) {
                if (
                    equipped.specialty_damage !== null &&
                    data.specialty_damage !== null
                ) {
                    return equipped.specialty_damage > 0;
                }
                return false;
            });
        };
    CharacterClassRankSpecialtiesSection.prototype.renderSpecialty =
        function () {
            var _this = this;
            if (this.state.special_selected === null) {
                return;
            }
            return React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    {
                        className:
                            "text-right cursor-pointer text-red-500 relative top-[10px] right-[20px]",
                    },
                    React.createElement(
                        "button",
                        {
                            onClick: function () {
                                return _this.manageViewSpecialty(null);
                            },
                        },
                        React.createElement("i", {
                            className: "fas fa-minus-circle",
                        }),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(
                        "h3",
                        {
                            className:
                                "text-sky-700 dark:text-sky-500 font-bold my-4",
                        },
                        this.state.special_selected.name,
                    ),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        this.state.special_selected.description,
                    ),
                    React.createElement(
                        "div",
                        { className: "grid lg:grid-cols-2 gap-2" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "h3",
                                null,
                                "Damage Information",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Required Attack Type:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    startCase(
                                        this.state.special_selected
                                            .attack_type_required,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Damage Amount:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    formatNumber(
                                        this.state.special_selected
                                            .specialty_damage,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Damage Increase per level:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    formatNumber(
                                        this.state.special_selected
                                            .increase_specialty_damage_per_level,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "% Of Damage Stat Used:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .specialty_damage_uses_damage_stat_amount,
                                    ),
                                    "%",
                                ),
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "h3",
                                { className: "my-4" },
                                "Evasion Bonus",
                            ),
                            React.createElement(
                                "p",
                                null,
                                "This will be applied to your spell evasion, which comes primarily from rings.",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                { className: "mb-4" },
                                React.createElement(
                                    "dt",
                                    null,
                                    "Spell Evasion",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .spell_evasion,
                                    ),
                                    "%",
                                ),
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "lg:hidden block border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                        }),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "h3",
                                null,
                                "Modifier Information",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                { className: "mb-4" },
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Damage Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .base_damage_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base AC Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected.base_ac_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Healing Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .base_healing_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Spell Damage Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .base_spell_damage_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Health Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected.health_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Damage Stat Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .base_damage_stat_increase,
                                    ),
                                    "%",
                                ),
                            ),
                            React.createElement("h3", null, "Reductions"),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Affix Damage Reduction",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .affix_damage_reduction,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Healing Reduction",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .healing_reduction,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Skill Reduction",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .skill_reduction,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Resistance Reduction",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.special_selected
                                            .resistance_reduction,
                                    ),
                                    "%",
                                ),
                            ),
                        ),
                    ),
                ),
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.renderSpecialtyEquipped =
        function (appendToTitle) {
            var _this = this;
            if (this.state.equipped_special === null) {
                return;
            }
            return React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    {
                        className:
                            "text-right cursor-pointer text-red-500 relative top-[10px]",
                    },
                    React.createElement(
                        "button",
                        {
                            onClick: function () {
                                return _this.manageViewSpecialtyEquipped(
                                    null,
                                    false,
                                );
                            },
                        },
                        React.createElement("i", {
                            className: "fas fa-minus-circle",
                        }),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    React.createElement(
                        "h3",
                        {
                            className:
                                "text-green-500 dark:text-green-400 font-bold my-4",
                        },
                        this.state.equipped_special.game_class_special.name,
                        " ",
                        this.state.show_equipped
                            ? appendToTitle !== null
                                ? "(" +
                                  appendToTitle +
                                  " Level: " +
                                  this.state.equipped_special.level +
                                  ")"
                                : "(Level: " +
                                  this.state.equipped_special.level +
                                  ")"
                            : "(Level: " +
                                  this.state.equipped_special.level +
                                  ")",
                    ),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        this.state.equipped_special.game_class_special
                            .description,
                    ),
                    React.createElement(
                        "div",
                        { className: "grid lg:grid-cols-2 gap-2" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "h3",
                                null,
                                "Damage Information",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Required Attack Type:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    startCase(
                                        this.state.equipped_special
                                            .game_class_special
                                            .attack_type_required,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Damage Amount:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    formatNumber(
                                        this.state.equipped_special
                                            .specialty_damage,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Damage Increase per level:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    formatNumber(
                                        this.state.equipped_special
                                            .increase_specialty_damage_per_level,
                                    ),
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "% Of Damage Stat Used:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special
                                            .game_class_special
                                            .specialty_damage_uses_damage_stat_amount,
                                    ),
                                    "%",
                                ),
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "lg:hidden block border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                        }),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "h3",
                                null,
                                "Modifier Information",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                            }),
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Damage Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special
                                            .base_damage_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base AC Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special.base_ac_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Healing Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special
                                            .base_healing_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Spell Damage Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special
                                            .base_spell_damage_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Health Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special.health_mod,
                                    ),
                                    "%",
                                ),
                                React.createElement(
                                    "dt",
                                    null,
                                    "Base Damage Stat Modifier:",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    this.renderPercent(
                                        this.state.equipped_special
                                            .base_damage_stat_increase,
                                    ),
                                    "%",
                                ),
                            ),
                        ),
                    ),
                ),
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.renderPercent = function (
        value,
    ) {
        if (value === null) {
            return 0;
        }
        return (value * 100).toFixed(2);
    };
    CharacterClassRankSpecialtiesSection.prototype.renderClassSpecialtiesTable =
        function () {
            return React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    { className: "mb-4" },
                    React.createElement(
                        InfoAlert,
                        { additional_css: "mb-4" },
                        'To reset the table below, select "Please select" when filtering by class.',
                    ),
                    React.createElement(
                        "div",
                        { className: "flex" },
                        React.createElement(
                            "div",
                            { className: "mr-4 dark:text-gray-300 mt-[10px]" },
                            "Class Filter",
                        ),
                        React.createElement(
                            "div",
                            { className: "w-1/2" },
                            React.createElement(Select, {
                                onChange: this.filterTableByClass.bind(this),
                                options: this.classOptions(true),
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
                                value: [
                                    {
                                        label:
                                            this.state.selected_filter !== null
                                                ? this.state.selected_filter
                                                : "Please Select",
                                        value:
                                            this.state.selected_filter !== null
                                                ? this.state.selected_filter
                                                : "Please Select",
                                    },
                                ],
                            }),
                        ),
                    ),
                ),
                React.createElement(Table, {
                    data: this.state.class_specials_for_table,
                    columns: this.classSpecialtiesTable(),
                    dark_table: this.state.dark_tables,
                }),
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.renderOtherClassMasteries =
        function () {
            return React.createElement(
                "div",
                null,
                React.createElement(
                    InfoAlert,
                    { additional_css: "mb-4" },
                    "These specialties are ones you have progression in but do not have equipped.",
                ),
                React.createElement(
                    "div",
                    { className: "flex mb-4" },
                    React.createElement(
                        "div",
                        { className: "mr-4 dark:text-gray-300 mt-[10px]" },
                        "Class Filter",
                    ),
                    React.createElement(
                        "div",
                        { className: "w-1/2" },
                        React.createElement(Select, {
                            onChange: this.filterOtherSpecialties.bind(this),
                            options: this.classOptions(false),
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
                            value: [
                                {
                                    label:
                                        this.state.other_selected_filter !==
                                        null
                                            ? this.state.other_selected_filter
                                            : "Please Select",
                                    value:
                                        this.state.other_selected_filter !==
                                        null
                                            ? this.state.other_selected_filter
                                            : "Please Select",
                                },
                            ],
                        }),
                    ),
                ),
                React.createElement(Table, {
                    data: this.state.other_class_specialties,
                    columns: this.classSpecialtiesEquippedTable(true),
                    dark_table: this.state.dark_tables,
                }),
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.renderSelectedType =
        function () {
            switch (this.props.selected_type) {
                case "class-specialties":
                    return this.renderClassSpecialtiesTable();
                case "equipped-specials":
                    return React.createElement(Table, {
                        data: this.state.specialties_equipped,
                        columns: this.classSpecialtiesEquippedTable(false),
                        dark_table: this.state.dark_tables,
                    });
                case "other-specialties":
                    return this.renderOtherClassMasteries();
                default:
                    return this.renderClassSpecialtiesTable();
            }
        };
    CharacterClassRankSpecialtiesSection.prototype.renderSpecialties =
        function () {
            return React.createElement(
                Fragment,
                null,
                this.state.equipping
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
                this.state.success_message !== null
                    ? React.createElement(
                          SuccessAlert,
                          null,
                          this.state.success_message,
                      )
                    : null,
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          null,
                          this.state.error_message,
                      )
                    : null,
                this.renderSelectedType(),
            );
        };
    CharacterClassRankSpecialtiesSection.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        return React.createElement(
            "div",
            { className: "max-h-[475px] lg:max-h-[525px] overflow-y-auto" },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "p-10" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : this.state.equipped_special !== null
                  ? this.renderSpecialtyEquipped("Equipped")
                  : this.state.special_selected !== null
                    ? this.renderSpecialty()
                    : this.renderSpecialties(),
        );
    };
    return CharacterClassRankSpecialtiesSection;
})(React.Component);
export default CharacterClassRankSpecialtiesSection;
//# sourceMappingURL=character-class-rank-specialties-section.js.map
