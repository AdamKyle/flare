import React, { Fragment } from "react";
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import { watchForDarkModeClassRankChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import CharacterClassRanksState from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/types/character-class-ranks-state";
import CharacterClassRanksProps from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/types/character-class-ranks-props";
import ClassRankType from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";
import WeaponMastery from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/weapon-mastery";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class CharacterClassRanks extends React.Component<
    CharacterClassRanksProps,
    CharacterClassRanksState
> {
    constructor(props: CharacterClassRanksProps) {
        super(props);

        const preloadedClassRanks = props.preloaded_class_ranks;

        this.state = {
            class_ranks:
                typeof preloadedClassRanks !== "undefined"
                    ? preloadedClassRanks
                    : [],
            dark_tables: false,
            loading: typeof preloadedClassRanks === "undefined",
            open_class_details: false,
            show_class_specialties: false,
            class_name_selected: null,
            switching_class: false,
            success_message: null,
            error_message: null,
        };
    }

    componentDidMount() {
        watchForDarkModeClassRankChange(this);

        if (typeof this.props.preloaded_class_ranks !== "undefined") {
            return;
        }

        new Ajax()
            .setRoute("class-ranks/" + this.props.character.id)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState({
                        class_ranks: response.data.class_ranks,
                        loading: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    manageViewClass(className: string | null) {
        const classNameSelected: ClassRankType = this.state.class_ranks.filter(
            (rank) => rank.class_name === className,
        )[0];

        this.setState({
            open_class_details: !this.state.open_class_details,
            class_name_selected: classNameSelected,
        });
    }

    switchClass(classId: number) {
        if (this.isAutomationRunning()) {
            return;
        }

        this.setState(
            {
                switching_class: true,
                success_message: null,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "switch-classes/" +
                            this.props.character.id +
                            "/" +
                            classId,
                    )
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState({
                                switching_class: false,
                                success_message: response.data.message,
                                class_ranks: response.data.class_ranks,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ switching_class: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message:
                                        "Something went wrong switching classes.",
                                });
                            }

                            console.error(error);
                        },
                    );
            },
        );
    }

    isAutomationRunning(): boolean {
        if (this.props.character === null) {
            return false;
        }

        return (
            this.props.character.is_automation_running ||
            this.props.character.is_delve_running ||
            this.props.character.is_faction_loyalty_automation_running
        );
    }

    tableColumns() {
        const columns = [
            {
                name: "Class name",
                selector: (row: { class_name: string }) => row.class_name,
                cell: (row: ClassRankType) => (
                    <button
                        onClick={() => this.manageViewClass(row.class_name)}
                        className={
                            "hover:underline text-blue-500 dark:text-blue-400"
                        }
                    >
                        {row.class_name}
                    </button>
                ),
            },
            {
                name: "Rank Level",
                selector: (row: { level: number }) => row.level,
                sortable: true,
            },
            {
                name: "XP",
                selector: (row: { current_xp: number; required_xp: number }) =>
                    row.current_xp,
                cell: (row: ClassRankType) => (
                    <span>
                        {formatNumber(row.current_xp) +
                            "/" +
                            formatNumber(row.required_xp)}
                    </span>
                ),
            },
            {
                name: "Active",
                selector: (row: { is_active: boolean }) => row.is_active,
                cell: (row: ClassRankType) => (
                    <span>{row.is_active ? "Yes" : "No"}</span>
                ),
            },
            {
                name: "Is Locked",
                selector: (row: { is_locked: boolean }) => row.is_locked,
                cell: (row: ClassRankType) => (
                    <span>{row.is_locked ? "Yes" : "No"}</span>
                ),
            },
        ];

        if (this.props.read_only) {
            return columns;
        }

        return [
            ...columns,
            {
                name: "Action",
                selector: (row: ClassRankType) => row.id,
                cell: (row: ClassRankType) => (
                    <span>
                        <PrimaryButton
                            button_label={"Switch To"}
                            on_click={() => this.switchClass(row.game_class_id)}
                            disabled={
                                row.is_active ||
                                row.is_locked ||
                                this.isAutomationRunning()
                            }
                        />
                    </span>
                ),
            },
        ];
    }

    masteryTableColumns() {
        return [
            {
                name: "Mastery",
                selector: (row: WeaponMastery) => row.mastery_name,
            },
            {
                name: "Level",
                selector: (row: WeaponMastery) => row.level,
                sortable: true,
                cell: (row: WeaponMastery) => (
                    <span>{formatNumber(row.level) + "/" + 100}</span>
                ),
            },
            {
                name: "Current XP",
                selector: (row: WeaponMastery) => row.current_xp,
                cell: (row: WeaponMastery) => (
                    <span>{formatNumber(row.current_xp)}</span>
                ),
            },
            {
                name: "Required XP",
                selector: (row: WeaponMastery) => row.required_xp,
                cell: (row: WeaponMastery) => (
                    <span>{formatNumber(row.required_xp)}</span>
                ),
            },
        ];
    }

    renderMasteriesOnlyForRank(rank: ClassRankType): JSX.Element {
        const offered = (this.props.preloaded_class_ranks_offered ?? []).find(
            (item) => item.class_id === rank.game_class_id,
        );
        const remainingMasteries = (
            offered?.remaining_weapon_masteries ?? []
        ).map((mastery) => ({
            mastery_name: mastery.name,
            level: mastery.level,
            current_xp: mastery.current_xp,
            required_xp: mastery.required_xp,
        }));

        return (
            <div key={rank.game_class_id} className="mb-8">
                <h2 className="text-sky-700 dark:text-sky-500 font-bold my-4">
                    {rank.class_name}
                </h2>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                <h3 className="my-3">Leveled Masteries</h3>
                {(rank.weapon_masteries ?? []).length > 0 ? (
                    <Table
                        data={rank.weapon_masteries}
                        columns={this.masteryTableColumns()}
                        dark_table={this.state.dark_tables}
                    />
                ) : (
                    <p>
                        No weapon masteries have been levelled for this class.
                    </p>
                )}

                <h3 className="my-3">Remaining Masteries</h3>
                {remainingMasteries.length > 0 ? (
                    <Table
                        data={remainingMasteries}
                        columns={this.masteryTableColumns()}
                        dark_table={this.state.dark_tables}
                    />
                ) : (
                    <p>
                        All weapon masteries for this class have been levelled.
                    </p>
                )}
            </div>
        );
    }

    renderMasteriesOnly() {
        return (
            <div>
                {this.state.class_ranks.map((rank: ClassRankType) =>
                    this.renderMasteriesOnlyForRank(rank),
                )}
            </div>
        );
    }

    manageClassSpecialties() {
        this.setState({
            show_class_specialties: !this.state.show_class_specialties,
        });
    }

    renderClassRanksOffered(rank: ClassRankType): JSX.Element {
        const offered = (this.props.preloaded_class_ranks_offered ?? []).find(
            (item) => item.class_id === rank.game_class_id,
        );
        const specialties = [
            ...(rank.equipped_specialties ?? []),
            ...(rank.unlocked_specialties ?? []),
        ].filter(
            (specialty, index, rows) =>
                rows.findIndex((row) => row.id === specialty.id) === index,
        );
        const currentRows = [
            { label: "Class Rank", value: `Level ${rank.level}` },
            ...(rank.weapon_masteries ?? []).map((mastery) => ({
                label: mastery.mastery_name,
                value: `Level ${mastery.level}`,
            })),
            ...(rank.current_class_skills ?? []).map((skill) => ({
                label: skill.name,
                value: `Level ${skill.level}`,
            })),
            ...specialties.map((specialty) => ({
                label: specialty.name,
                value: `Level ${specialty.level}`,
            })),
        ];
        const remainingRows =
            offered === undefined
                ? []
                : [
                      ...offered.offered_game_skills.map((skill) => ({
                          label: skill.name,
                          value: "Class Skill",
                      })),
                      ...offered.remaining_weapon_masteries.map((mastery) => ({
                          label: mastery.name,
                          value: "Weapon Mastery",
                      })),
                      ...offered.remaining_specialties.map((specialty) => ({
                          label: specialty.name,
                          value: "Class Specialty",
                      })),
                  ];

        return (
            <section className="mt-6" aria-label="Class Ranks detail">
                <h3 className="my-3">Class Ranks</h3>
                <p className="mb-4">
                    Class skills are learned by switching into and using
                    different classes. These skills apply while you are using
                    the related class and can change or improve that class’s
                    behavior.{" "}
                    <a
                        href="/information/class-skills"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Learn about class skills{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    .
                </p>
                <div
                    className={
                        remainingRows.length > 0
                            ? "grid gap-4 lg:grid-cols-2"
                            : "grid gap-4"
                    }
                >
                    <dl className="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-2">
                        {currentRows.map((row) => (
                            <Fragment key={`${row.label}-${row.value}`}>
                                <dt className="font-semibold">{row.label}</dt>
                                <dd>{row.value}</dd>
                            </Fragment>
                        ))}
                    </dl>
                    {remainingRows.length > 0 ? (
                        <dl className="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-2">
                            {remainingRows.map((row) => (
                                <Fragment key={`${row.label}-${row.value}`}>
                                    <dt className="font-semibold">
                                        {row.label}
                                    </dt>
                                    <dd>{row.value}</dd>
                                </Fragment>
                            ))}
                        </dl>
                    ) : null}
                </div>
            </section>
        );
    }

    render() {
        if (this.state.loading) {
            return (
                <div className="relative my-6 p-[20px]">
                    <LoadingProgressBar />
                </div>
            );
        }

        if (this.props.read_only && this.props.masteries_only) {
            return <div>{this.renderMasteriesOnly()}</div>;
        }

        return (
            <div
                className={
                    this.props.read_only ? "" : "max-h-[375px] overflow-y-auto"
                }
            >
                {this.state.open_class_details &&
                this.state.class_name_selected !== null ? (
                    <div>
                        <div className="text-right cursor-pointer text-red-500 relative top-[10px] right-[10px]">
                            <button onClick={() => this.manageViewClass(null)}>
                                <i className="fas fa-minus-circle"></i>
                            </button>
                        </div>

                        <h2 className="text-sky-700 dark:text-sky-500 font-bold my-4">
                            {this.state.class_name_selected.class_name}
                        </h2>

                        <p className="mb-4">
                            To learn more about this class, checkout{" "}
                            <a
                                href={
                                    "/information/class/" +
                                    this.state.class_name_selected.game_class_id
                                }
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                the class documentation{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            to learn more about special attacks and tips and
                            tricks to play the class to the fullest advantage.
                        </p>

                        <p className="mb-4">
                            When you switch to this class, your current class
                            skill be hidden and you will now have an opportunity
                            to level this classes skill in the skill section for
                            trainable skills. Click the above link to learn more
                            about the class.
                        </p>

                        <div className="grid lg:grid-cols-2 gap-2 mb-4">
                            <div>
                                {this.state.class_name_selected.game_class ? (
                                    <Fragment>
                                        <h3 className="my-3">
                                            Base Information
                                        </h3>
                                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                        <dl className="mb-4">
                                            <dt>Base Damage Stat</dt>
                                            <dd>
                                                {
                                                    this.state
                                                        .class_name_selected
                                                        .game_class.to_hit_stat
                                                }
                                            </dd>
                                            <dt>Accuracy Mod</dt>
                                            <dd>
                                                +
                                                {(
                                                    this.state
                                                        .class_name_selected
                                                        .game_class
                                                        .looting_mod * 100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                            <dt>Looting Mod</dt>
                                            <dd>
                                                +
                                                {(
                                                    this.state
                                                        .class_name_selected
                                                        .game_class
                                                        .accuracy_mod * 100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                        </dl>
                                    </Fragment>
                                ) : null}

                                {this.state.class_name_selected
                                    .secondary_class_name &&
                                this.state.class_name_selected
                                    .primary_class_name ? (
                                    <Fragment>
                                        <h3 className="my-3">Requirements</h3>
                                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                        <p className="mb-2">
                                            This class may require you to level
                                            other classes before being able to
                                            switch to this class. To do so,
                                            follow the below information to
                                            unlock the class!
                                        </p>
                                        <dl className="mb-4">
                                            <dt>Primary Class Required:</dt>
                                            <dd>
                                                {
                                                    this.state
                                                        .class_name_selected
                                                        .primary_class_name
                                                }
                                            </dd>
                                            <dt>
                                                Primary Class Rank Level
                                                Required:
                                            </dt>
                                            <dd>
                                                {
                                                    this.state
                                                        .class_name_selected
                                                        .primary_class_required_level
                                                }
                                            </dd>
                                            <dt>Secondary Class Required:</dt>
                                            <dd>
                                                {
                                                    this.state
                                                        .class_name_selected
                                                        .secondary_class_name
                                                }
                                            </dd>
                                            <dt>
                                                Secondary Class Rank Level
                                                Required:
                                            </dt>
                                            <dd>
                                                {
                                                    this.state
                                                        .class_name_selected
                                                        .secondary_class_required_level
                                                }
                                            </dd>
                                        </dl>
                                    </Fragment>
                                ) : null}
                            </div>
                            <div className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <div>
                                <h3 className="my-3">Class Masteries</h3>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <p className="my-4">
                                    The more levels you put in, by using a
                                    specific weapon type, the more % bonus
                                    towards that weapon types damage.
                                </p>
                                <Table
                                    data={
                                        this.state.class_name_selected
                                            .weapon_masteries
                                    }
                                    columns={this.masteryTableColumns()}
                                    dark_table={this.state.dark_tables}
                                />
                            </div>
                        </div>

                        {this.props.read_only
                            ? this.renderClassRanksOffered(
                                  this.state.class_name_selected,
                              )
                            : null}
                    </div>
                ) : (
                    <Fragment>
                        {!this.props.read_only && this.state.switching_class ? (
                            <LoadingProgressBar />
                        ) : null}
                        {!this.props.read_only &&
                        this.state.error_message !== null ? (
                            <DangerAlert additional_css={"my-4"}>
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}
                        {!this.props.read_only &&
                        this.state.success_message !== null ? (
                            <SuccessAlert additional_css={"my-4"}>
                                {this.state.success_message}
                            </SuccessAlert>
                        ) : null}
                        <Table
                            data={this.state.class_ranks}
                            columns={this.tableColumns()}
                            dark_table={this.state.dark_tables}
                        />
                    </Fragment>
                )}
            </div>
        );
    }
}
