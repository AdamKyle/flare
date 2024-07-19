import React, { Fragment } from "react";
import Dialogue from "../../../ui/dialogue/dialogue";
import Tabs from "../../../ui/tabs/tabs";
import TabPanel from "../../../ui/tabs/tab-panel";
import { formatNumber } from "../../../../lib/game/format-number";
import ComponentLoading from "../../../ui/loading/component-loading";
import ClassSpecialtiesEquippedProps from "../../../../lib/game/character-sheet/types/modal/class-specialties-equipped-props";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ClassSpecialtiesState from "../types/sections/class-ranks/types/class-specialties-state";
import ClassSpecialtiesType from "../types/sections/class-ranks/deffinitions/class-specialties-type";
import PrimaryButton from "../../../ui/buttons/primary-button";
import { watchForDarkModeClassSpecialtyChange } from "../../../../lib/game/dark-mode-watcher";
import Table from "../../../ui/data-tables/table";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import CharacterSpecialsEquippedTyp from "../types/sections/class-ranks/deffinitions/character-specials-equipped-typ";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import { startCase } from "lodash";
import Select from "react-select";
import InfoAlert from "../../../ui/alerts/simple-alerts/info-alert";
import ClassRankType from "../types/sections/class-ranks/deffinitions/class-rank-type";

export default class CharacterClassRankSpecialtiesSection extends React.Component<
    ClassSpecialtiesEquippedProps,
    ClassSpecialtiesState
> {
    private tabs: { key: string; name: string }[];

    constructor(props: ClassSpecialtiesEquippedProps) {
        super(props);

        this.state = {
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

        this.tabs = [
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
    }

    componentDidMount() {
        watchForDarkModeClassSpecialtyChange(this);

        if (this.props.character === null) {
            return;
        }

        new Ajax()
            .setRoute("class-ranks/" + this.props.character.id + "/specials")
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState(
                        {
                            loading: false,
                            class_specialties: response.data.class_specialties,
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
                                this.props.character === null
                                    ? null
                                    : this.props.character.class,
                            other_selected_filter:
                                this.props.character === null
                                    ? null
                                    : this.props.character.class,
                        },
                        () => {
                            this.filterTable();
                            this.filterOtherClassSpecialsTable();
                        },
                    );
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    filterTableByClass(data: any) {
        if (data.value === "Please select") {
            this.setState(
                {
                    class_specials_for_table: this.state.class_specialties,
                    selected_filter:
                        this.props.character === null
                            ? null
                            : this.props.character.class,
                },
                () => {
                    this.filterTable();
                    this.filterOtherClassSpecialsTable();
                },
            );

            return;
        }

        this.setState(
            {
                selected_filter: data.value,
            },
            () => {
                this.filterTable();
            },
        );
    }

    filterOtherSpecialties(data: any) {
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
            () => {
                this.filterOtherClassSpecialsTable();
            },
        );
    }

    filterTable() {
        if (this.state.selected_filter === null) {
            return;
        }

        const classSpecialties = JSON.parse(
            JSON.stringify(this.state.class_specialties),
        );

        if (this.state.selected_filter === "Equippable") {
            const specials = classSpecialties.filter(
                (special: ClassSpecialtiesType) => {
                    const ranks = this.state.class_ranks.filter(
                        (rank: ClassRankType) => {
                            if (rank.game_class_id === special.game_class_id) {
                                return (
                                    rank.level >=
                                    special.requires_class_rank_level
                                );
                            }
                        },
                    );

                    if (ranks.length > 0) {
                        return special;
                    }
                },
            );

            this.setState({
                class_specials_for_table: specials,
            });

            return;
        }

        this.setState({
            class_specials_for_table: classSpecialties.filter(
                (special: ClassSpecialtiesType) =>
                    special.class_name === this.state.selected_filter,
            ),
        });
    }

    filterOtherClassSpecialsTable() {
        if (this.state.other_class_specialties === null) {
            return;
        }

        const otherSpecials = JSON.parse(
            JSON.stringify(this.state.original_class_specialties),
        );

        if (otherSpecials.length === 0) {
            return;
        }

        this.setState({
            other_class_specialties: otherSpecials.filter(
                (special: ClassSpecialtiesType) =>
                    special.class_name === this.state.other_selected_filter,
            ),
        });
    }

    classOptions(addEquippable: boolean) {
        const classes = [
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

        return classes.map((className) => {
            return {
                label: className,
                value: className,
            };
        });
    }

    unequipSpecial(specialId: number) {
        this.setState(
            {
                equipping: true,
                equipping_special_id: specialId,
                success_message: null,
                error_message: null,
            },
            () => {
                if (this.props.character === null) {
                    return;
                }

                new Ajax()
                    .setRoute(
                        "unequip-specialty/" +
                            this.props.character.id +
                            "/" +
                            specialId,
                    )
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState(
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
                                () => {
                                    this.filterTable();
                                    this.filterOtherClassSpecialsTable();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ equipping: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                if (typeof error.response !== "undefined") {
                                    this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            }
                        },
                    );
            },
        );
    }

    equipSpecial(specialId: number) {
        this.setState(
            {
                equipping: true,
                equipping_special_id: specialId,
                success_message: null,
                error_message: null,
            },
            () => {
                if (this.props.character === null) {
                    return;
                }

                new Ajax()
                    .setRoute(
                        "equip-specialty/" +
                            this.props.character.id +
                            "/" +
                            specialId,
                    )
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState(
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
                                () => {
                                    this.filterTable();
                                    this.filterOtherClassSpecialsTable();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ equipping: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    doesSpecialtyDealDamage(specialty: ClassSpecialtiesType): boolean {
        if (specialty.specialty_damage !== null) {
            return specialty.specialty_damage > 0;
        }

        return false;
    }

    classSpecialtiesTable() {
        return [
            {
                name: "Name",
                selector: (row: ClassSpecialtiesType) => row.name,
                cell: (row: ClassSpecialtiesType) => (
                    <Fragment>
                        <button
                            className="hover:underline text-blue-500 dark:text-blue-400"
                            onClick={() => this.manageViewSpecialty(row)}
                        >
                            {row.name}
                        </button>
                    </Fragment>
                ),
            },
            {
                name: "Class Name",
                selector: (row: ClassSpecialtiesType) => row.class_name,
            },
            {
                name: "Class Rank Required",
                selector: (row: ClassSpecialtiesType) =>
                    row.requires_class_rank_level,
            },
            {
                name: "Deals Damage",
                selector: (row: ClassSpecialtiesType) =>
                    this.doesSpecialtyDealDamage(row) ? "Yes" : "No",
            },
            {
                name: "Actions",
                selector: (row: ClassSpecialtiesType) => row.id,
                cell: (row: ClassSpecialtiesType) => (
                    <Fragment>
                        {this.specialtyIsEquipped(row.id) ? (
                            <span className="text-green-500 dark:text-green-400">
                                Specialty is equipped
                            </span>
                        ) : this.hasDamageSpecialtyEquipped(row) ? (
                            <span className="text-red-500 dark:text-red-400">
                                You already have a damage specialty equipped.
                            </span>
                        ) : (
                            <PrimaryButton
                                button_label={
                                    this.state.equipping &&
                                    this.state.equipping_special_id ===
                                        row.id ? (
                                        <Fragment>
                                            <i className="fas fa-spinner fa-spin"></i>{" "}
                                            Equip
                                        </Fragment>
                                    ) : (
                                        "Equip"
                                    )
                                }
                                on_click={() => this.equipSpecial(row.id)}
                                disabled={this.isEquipButtonDisabled(
                                    row.requires_class_rank_level,
                                    row.game_class_id,
                                )}
                            />
                        )}
                    </Fragment>
                ),
            },
        ];
    }

    specialtyIsEquipped(specialtyId: number): boolean {
        return (
            this.state.specialties_equipped.filter(
                (specialty: CharacterSpecialsEquippedTyp) => {
                    return specialtyId === specialty.game_class_special_id;
                },
            ).length > 0
        );
    }

    classSpecialtiesEquippedTable(equipSpecial: boolean) {
        return [
            {
                name: "Name",
                selector: (row: CharacterSpecialsEquippedTyp) =>
                    row.game_class_special.name,
                cell: (row: CharacterSpecialsEquippedTyp) => (
                    <Fragment>
                        <button
                            className="hover:underline text-blue-500 dark:text-blue-400"
                            onClick={() =>
                                this.manageViewSpecialtyEquipped(
                                    row,
                                    equipSpecial ? false : true,
                                )
                            }
                        >
                            {row.game_class_special.name}
                        </button>
                    </Fragment>
                ),
            },
            {
                name: "Class Name",
                selector: (row: CharacterSpecialsEquippedTyp) => row.class_name,
            },
            {
                name: "Level",
                selector: (row: CharacterSpecialsEquippedTyp) => row.level,
            },
            {
                name: "XP",
                selector: (row: CharacterSpecialsEquippedTyp) => row.id,
                cell: (row: CharacterSpecialsEquippedTyp) => (
                    <Fragment>
                        {formatNumber(row.current_xp)}/
                        {formatNumber(row.required_xp)}
                    </Fragment>
                ),
            },
            {
                name: "Deals Damage",
                selector: (row: ClassSpecialtiesType) =>
                    this.doesSpecialtyDealDamage(row) ? "Yes" : "No",
            },
            {
                name: "Actions",
                selector: (row: CharacterSpecialsEquippedTyp) => row.id,
                cell: (row: CharacterSpecialsEquippedTyp) => (
                    <Fragment>
                        {equipSpecial ? (
                            this.specialtyIsEquipped(row.id) ? (
                                <span className="text-green-500 dark:text-green-400">
                                    Specialty is equipped
                                </span>
                            ) : this.hasDamageSpecialtyEquipped(row) ? (
                                <span className="text-red-500 dark:text-red-400">
                                    You already have a damage specialty
                                    equipped.
                                </span>
                            ) : (
                                <PrimaryButton
                                    button_label={
                                        this.state.equipping &&
                                        this.state.equipping_special_id ===
                                            row.game_class_special_id ? (
                                            <Fragment>
                                                <i className="fas fa-spinner fa-spin"></i>{" "}
                                                Equip
                                            </Fragment>
                                        ) : (
                                            "Equip"
                                        )
                                    }
                                    on_click={() =>
                                        this.equipSpecial(
                                            row.game_class_special_id,
                                        )
                                    }
                                    disabled={this.isEquipButtonDisabled(
                                        row.game_class_special
                                            .requires_class_rank_level,
                                        row.game_class_special.game_class_id,
                                    )}
                                />
                            )
                        ) : (
                            <PrimaryButton
                                button_label={
                                    this.state.equipping &&
                                    this.state.equipping_special_id ===
                                        row.id ? (
                                        <Fragment>
                                            <i className="fas fa-spinner fa-spin"></i>{" "}
                                            Unequip
                                        </Fragment>
                                    ) : (
                                        "Unequip"
                                    )
                                }
                                on_click={() => this.unequipSpecial(row.id)}
                                disabled={this.state.equipping}
                            />
                        )}
                    </Fragment>
                ),
            },
        ];
    }

    isEquipButtonDisabled(requiredLevel: number, classId: number): boolean {
        if (this.state.equipping) {
            return true;
        }

        const rank = this.state.class_ranks.filter(
            (rank: ClassRankType) => rank.game_class_id === classId,
        )[0];

        return rank.level < requiredLevel;
    }

    manageViewSpecialty(specialty: ClassSpecialtiesType | null) {
        this.setState({
            special_selected: specialty,
        });
    }

    manageViewSpecialtyEquipped(
        equippedSpecialty: CharacterSpecialsEquippedTyp | null,
        showIsEquipped: boolean,
    ) {
        this.setState({
            equipped_special: equippedSpecialty,
            show_equipped: showIsEquipped,
        });
    }

    hasDamageSpecialtyEquipped(
        data: CharacterSpecialsEquippedTyp | ClassSpecialtiesType,
    ): boolean {
        return this.state.specialties_equipped.some(
            (equipped: CharacterSpecialsEquippedTyp) => {
                if (
                    equipped.specialty_damage !== null &&
                    data.specialty_damage !== null
                ) {
                    return equipped.specialty_damage > 0;
                }

                return false;
            },
        );
    }

    renderSpecialty() {
        if (this.state.special_selected === null) {
            return;
        }

        return (
            <div>
                <div className="text-right cursor-pointer text-red-500 relative top-[10px] right-[20px]">
                    <button onClick={() => this.manageViewSpecialty(null)}>
                        <i className="fas fa-minus-circle"></i>
                    </button>
                </div>
                <div className="my-4">
                    <h3 className="text-sky-700 dark:text-sky-500 font-bold my-4">
                        {this.state.special_selected.name}
                    </h3>
                    <p className="my-4">
                        {this.state.special_selected.description}
                    </p>
                    <div className="grid lg:grid-cols-2 gap-2">
                        <div>
                            <h3>Damage Information</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl>
                                <dt>Required Attack Type:</dt>
                                <dd>
                                    {startCase(
                                        this.state.special_selected
                                            .attack_type_required,
                                    )}
                                </dd>
                                <dt>Damage Amount:</dt>
                                <dd>
                                    {formatNumber(
                                        this.state.special_selected
                                            .specialty_damage,
                                    )}
                                </dd>
                                <dt>Damage Increase per level:</dt>
                                <dd>
                                    {formatNumber(
                                        this.state.special_selected
                                            .increase_specialty_damage_per_level,
                                    )}
                                </dd>
                                <dt>% Of Damage Stat Used:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .specialty_damage_uses_damage_stat_amount,
                                    )}
                                    %
                                </dd>
                            </dl>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <h3 className="my-4">Evasion Bonus</h3>
                            <p>
                                This will be applied to your spell evasion,
                                which comes primarily from rings.
                            </p>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl className="mb-4">
                                <dt>Spell Evasion</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .spell_evasion,
                                    )}
                                    %
                                </dd>
                            </dl>
                        </div>
                        <div className="lg:hidden block border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                        <div>
                            <h3>Modifier Information</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl className="mb-4">
                                <dt>Base Damage Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .base_damage_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base AC Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected.base_ac_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Healing Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .base_healing_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Spell Damage Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .base_spell_damage_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Health Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected.health_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Damage Stat Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .base_damage_stat_increase,
                                    )}
                                    %
                                </dd>
                            </dl>
                            <h3>Reductions</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl>
                                <dt>Affix Damage Reduction</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .affix_damage_reduction,
                                    )}
                                    %
                                </dd>
                                <dt>Healing Reduction</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .healing_reduction,
                                    )}
                                    %
                                </dd>
                                <dt>Skill Reduction</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .skill_reduction,
                                    )}
                                    %
                                </dd>
                                <dt>Resistance Reduction</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.special_selected
                                            .resistance_reduction,
                                    )}
                                    %
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    renderSpecialtyEquipped(appendToTitle: string | null) {
        if (this.state.equipped_special === null) {
            return;
        }

        return (
            <div>
                <div className="text-right cursor-pointer text-red-500 relative top-[10px]">
                    <button
                        onClick={() =>
                            this.manageViewSpecialtyEquipped(null, false)
                        }
                    >
                        <i className="fas fa-minus-circle"></i>
                    </button>
                </div>
                <div className="my-4">
                    <h3 className="text-green-500 dark:text-green-400 font-bold my-4">
                        {this.state.equipped_special.game_class_special.name}{" "}
                        {this.state.show_equipped
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
                              ")"}
                    </h3>
                    <p className="my-4">
                        {
                            this.state.equipped_special.game_class_special
                                .description
                        }
                    </p>
                    <div className="grid lg:grid-cols-2 gap-2">
                        <div>
                            <h3>Damage Information</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl>
                                <dt>Required Attack Type:</dt>
                                <dd>
                                    {startCase(
                                        this.state.equipped_special
                                            .game_class_special
                                            .attack_type_required,
                                    )}
                                </dd>
                                <dt>Damage Amount:</dt>
                                <dd>
                                    {formatNumber(
                                        this.state.equipped_special
                                            .specialty_damage,
                                    )}
                                </dd>
                                <dt>Damage Increase per level:</dt>
                                <dd>
                                    {formatNumber(
                                        this.state.equipped_special
                                            .increase_specialty_damage_per_level,
                                    )}
                                </dd>
                                <dt>% Of Damage Stat Used:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special
                                            .game_class_special
                                            .specialty_damage_uses_damage_stat_amount,
                                    )}
                                    %
                                </dd>
                            </dl>
                        </div>
                        <div className="lg:hidden block border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                        <div>
                            <h3>Modifier Information</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl>
                                <dt>Base Damage Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special
                                            .base_damage_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base AC Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special.base_ac_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Healing Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special
                                            .base_healing_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Spell Damage Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special
                                            .base_spell_damage_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Health Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special.health_mod,
                                    )}
                                    %
                                </dd>
                                <dt>Base Damage Stat Modifier:</dt>
                                <dd>
                                    {this.renderPercent(
                                        this.state.equipped_special
                                            .base_damage_stat_increase,
                                    )}
                                    %
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    renderPercent(value: number | null) {
        if (value === null) {
            return 0;
        }

        return (value * 100).toFixed(2);
    }

    renderClassSpecialtiesTable() {
        return (
            <div>
                <div className="mb-4">
                    <InfoAlert additional_css="mb-4">
                        To reset the table below, select "Please select" when
                        filtering by class.
                    </InfoAlert>
                    <div className="flex">
                        <div className="mr-4 dark:text-gray-300 mt-[10px]">
                            Class Filter
                        </div>
                        <div className="w-1/2">
                            <Select
                                onChange={this.filterTableByClass.bind(this)}
                                options={this.classOptions(true)}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={[
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
                                ]}
                            />
                        </div>
                    </div>
                </div>
                <Table
                    data={this.state.class_specials_for_table}
                    columns={this.classSpecialtiesTable()}
                    dark_table={this.state.dark_tables}
                />
            </div>
        );
    }

    renderOtherClassMasteries() {
        return (
            <div>
                <InfoAlert additional_css="mb-4">
                    These specialties are ones you have progression in but do
                    not have equipped.
                </InfoAlert>
                <div className="flex mb-4">
                    <div className="mr-4 dark:text-gray-300 mt-[10px]">
                        Class Filter
                    </div>
                    <div className="w-1/2">
                        <Select
                            onChange={this.filterOtherSpecialties.bind(this)}
                            options={this.classOptions(false)}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={[
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
                            ]}
                        />
                    </div>
                </div>
                <Table
                    data={this.state.other_class_specialties}
                    columns={this.classSpecialtiesEquippedTable(true)}
                    dark_table={this.state.dark_tables}
                />
            </div>
        );
    }

    renderSelectedType() {
        switch (this.props.selected_type) {
            case "class-specialties":
                return this.renderClassSpecialtiesTable();
            case "equipped-specials":
                return (
                    <Table
                        data={this.state.specialties_equipped}
                        columns={this.classSpecialtiesEquippedTable(false)}
                        dark_table={this.state.dark_tables}
                    />
                );
            case "other-specialties":
                return this.renderOtherClassMasteries();
            default:
                return this.renderClassSpecialtiesTable();
        }
    }

    renderSpecialties() {
        return (
            <Fragment>
                {this.state.equipping ? <LoadingProgressBar /> : null}
                {this.state.success_message !== null ? (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                ) : null}

                {this.state.error_message !== null ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.renderSelectedType()}
            </Fragment>
        );
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        return (
            <div className="max-h-[475px] lg:max-h-[525px] overflow-y-auto">
                {this.state.loading ? (
                    <div className="p-10">
                        <LoadingProgressBar />
                    </div>
                ) : this.state.equipped_special !== null ? (
                    this.renderSpecialtyEquipped("Equipped")
                ) : this.state.special_selected !== null ? (
                    this.renderSpecialty()
                ) : (
                    this.renderSpecialties()
                )}
            </div>
        );
    }
}
