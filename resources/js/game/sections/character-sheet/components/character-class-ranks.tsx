import React, { Fragment } from "react";
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import { watchForDarkModeClassRankChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import CharacterClassRanksState from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/types/character-class-ranks-state";
import CharacterClassRanksProps from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/types/character-class-ranks-props";
import ClassRankType from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Select, { SingleValue, StylesConfig } from "react-select";
import { Transition } from "@headlessui/react";

const masterySelectStyles: StylesConfig<any, false> = {
    menu: (base) => ({
        ...base,
        backgroundColor: "#ffffff",
        color: "#111827",
    }),
    menuPortal: (base) => ({ ...base, zIndex: 9999 }),
    option: (base, state) => ({
        ...base,
        backgroundColor: state.isDisabled
            ? "#ffffff"
            : state.isSelected
              ? "#2684ff"
              : state.isFocused
                ? "#deebff"
                : "#ffffff",
        color: state.isDisabled
            ? "#9ca3af"
            : state.isSelected
              ? "#ffffff"
              : "#111827",
    }),
};

export default class CharacterClassRanks extends React.Component<
    CharacterClassRanksProps,
    CharacterClassRanksState
> {
    constructor(props: CharacterClassRanksProps) {
        super(props);

        const preloadedClassRanks = props.preloaded_class_ranks;

        const defaultMasteryRank =
            preloadedClassRanks?.find((rank) => rank.is_active) ??
            [...(preloadedClassRanks ?? [])].sort(
                (first, second) => second.level - first.level,
            )[0];

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
            mastery_class_id: defaultMasteryRank?.game_class_id ?? null,
            selected_mastery: null,
            mastery_details_visible: false,
            pending_mastery: null,
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
                        type="button"
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
                selector: (row: { mastery_name: string }) => row.mastery_name,
                cell: (row: CharacterClassRanksState["selected_mastery"]) =>
                    row ? (
                        <button
                            type="button"
                            aria-expanded={
                                this.state.selected_mastery?.id === row.id &&
                                this.state.mastery_details_visible
                            }
                            aria-controls={this.masteryDetailsId(row)}
                            className="rounded text-left text-blue-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-400"
                            onClick={(event) => {
                                event.stopPropagation();
                                this.toggleMastery(row);
                            }}
                        >
                            {row.mastery_name}
                        </button>
                    ) : null,
            },
            {
                name: "Level",
                selector: (row: { level: number }) => row.level,
                cell: (row: { level: number }) => `${row.level}/100`,
            },
        ];
    }

    masteryDetailsId(
        mastery: NonNullable<CharacterClassRanksState["selected_mastery"]>,
    ): string {
        return `mastery-details-${this.state.mastery_class_id}-${mastery.id}`;
    }

    toggleMastery(
        mastery: NonNullable<CharacterClassRanksState["selected_mastery"]>,
    ): void {
        if (this.state.selected_mastery === null) {
            this.setState({
                selected_mastery: mastery,
                mastery_details_visible: true,
                pending_mastery: null,
            });

            return;
        }

        this.setState({
            mastery_details_visible: false,
            pending_mastery:
                this.state.selected_mastery.id === mastery.id ? null : mastery,
        });
    }

    finishMasteryLeave(): void {
        this.setState((state) => ({
            selected_mastery: state.pending_mastery,
            mastery_details_visible: state.pending_mastery !== null,
            pending_mastery: null,
        }));
    }

    renderExpandedMastery(
        mastery: NonNullable<CharacterClassRanksState["selected_mastery"]>,
    ): JSX.Element {
        return (
            <Transition
                as={Fragment}
                appear
                show={
                    this.state.mastery_details_visible &&
                    this.state.selected_mastery?.id === mastery.id
                }
                enter="transition-all duration-200 ease-out overflow-hidden"
                enterFrom="max-h-0 opacity-0"
                enterTo="max-h-[1000px] opacity-100"
                leave="transition-all duration-150 ease-in overflow-hidden"
                leaveFrom="max-h-[1000px] opacity-100"
                leaveTo="max-h-0 opacity-0"
                afterLeave={() => this.finishMasteryLeave()}
            >
                <section
                    id={this.masteryDetailsId(mastery)}
                    aria-label={`${mastery.mastery_name} details`}
                    className="max-w-full overflow-hidden bg-gray-50 p-4 text-sm text-gray-900 dark:bg-gray-900 dark:text-gray-100"
                >
                    <dl className="grid grid-cols-[max-content_minmax(0,1fr)] gap-x-4 gap-y-2 break-words">
                        <dt className="font-semibold">Mastery name</dt>
                        <dd>{mastery.mastery_name}</dd>
                        <dt className="font-semibold">Associated class</dt>
                        <dd>{mastery.associated_class}</dd>
                        <dt className="font-semibold">Level</dt>
                        <dd>{mastery.level}/100</dd>
                        <dt className="font-semibold">Current XP</dt>
                        <dd>{formatNumber(mastery.current_xp)}</dd>
                        <dt className="font-semibold">Required XP</dt>
                        <dd>{formatNumber(mastery.required_xp)}</dd>
                        <dt className="font-semibold">XP percentage</dt>
                        <dd>
                            {mastery.required_xp > 0
                                ? Math.min(
                                      100,
                                      (mastery.current_xp /
                                          mastery.required_xp) *
                                          100,
                                  ).toFixed(2)
                                : "100.00"}
                            %
                        </dd>
                        <dt className="font-semibold">Weapon type</dt>
                        <dd>{String(mastery.weapon_type)}</dd>
                        <dt className="font-semibold">Status</dt>
                        <dd>{mastery.is_leveled ? "Leveled" : "Remaining"}</dd>
                    </dl>
                </section>
            </Transition>
        );
    }

    renderMasteriesOnlyForRank(rank: ClassRankType): JSX.Element {
        const offered = (this.props.preloaded_class_ranks_offered ?? []).find(
            (item) => item.class_id === rank.game_class_id,
        );
        const remainingMasteries = (
            offered?.remaining_weapon_masteries ?? []
        ).map((mastery) => ({
            id: mastery.id,
            mastery_name: mastery.name,
            weapon_type: mastery.weapon_type,
            level: mastery.level,
            current_xp: mastery.current_xp,
            required_xp: mastery.required_xp,
        }));

        const masteries = [
            ...(rank.weapon_masteries ?? []).map((mastery) => ({
                ...mastery,
                is_leveled: true,
                associated_class: rank.class_name,
            })),
            ...remainingMasteries.map((mastery) => ({
                ...mastery,
                is_leveled: false,
                associated_class: rank.class_name,
            })),
        ];
        const masteryGroups = [
            {
                title: "Leveled Masteries",
                rows: masteries.filter((mastery) => mastery.is_leveled),
            },
            {
                title: "Remaining Masteries",
                rows: masteries.filter((mastery) => !mastery.is_leveled),
            },
        ];

        return (
            <div key={rank.game_class_id} className="mb-8">
                <h2 className="my-4 font-bold text-sky-700 dark:text-sky-500">
                    {rank.class_name}
                </h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    {masteryGroups.map((group) => (
                        <section
                            key={group.title}
                            aria-labelledby={`mastery-${group.title.replaceAll(" ", "-")}`}
                            className="min-w-0"
                        >
                            <h3
                                id={`mastery-${group.title.replaceAll(" ", "-")}`}
                                className="mb-2 font-semibold"
                            >
                                {group.title}
                            </h3>
                            {group.rows.length > 0 ? (
                                <Table
                                    data={group.rows}
                                    columns={this.masteryTableColumns()}
                                    dark_table={this.state.dark_tables}
                                    expandable_rows={true}
                                    expandable_rows_hide_expander={true}
                                    expandable_row_expanded={(row) =>
                                        this.state.selected_mastery?.id ===
                                        row.id
                                    }
                                    expandable_rows_component={({ data }) =>
                                        this.renderExpandedMastery(data)
                                    }
                                    on_row_clicked={(row) =>
                                        this.toggleMastery(row)
                                    }
                                    on_change_page={() =>
                                        this.setState({
                                            selected_mastery: null,
                                            mastery_details_visible: false,
                                            pending_mastery: null,
                                        })
                                    }
                                />
                            ) : (
                                <p>None.</p>
                            )}
                        </section>
                    ))}
                </div>
            </div>
        );
    }

    renderMasteriesOnly() {
        const options = this.state.class_ranks.map((rank) => ({
            label: rank.class_name,
            value: rank.game_class_id,
        }));
        const selectedRank =
            this.state.class_ranks.find(
                (rank) => rank.game_class_id === this.state.mastery_class_id,
            ) ?? this.state.class_ranks[0];

        return (
            <div>
                <label className="block text-sm font-semibold">
                    Class
                    <Select
                        className="mt-1 text-sm"
                        classNamePrefix="react-select"
                        aria-label="Select class masteries"
                        options={options}
                        value={
                            options.find(
                                (option) =>
                                    option.value ===
                                    selectedRank?.game_class_id,
                            ) ?? null
                        }
                        onChange={(
                            option: SingleValue<{
                                label: string;
                                value: number;
                            }>,
                        ) =>
                            this.setState({
                                mastery_class_id: option?.value ?? null,
                                selected_mastery: null,
                                mastery_details_visible: false,
                                pending_mastery: null,
                            })
                        }
                        styles={masterySelectStyles}
                        menuPortalTarget={document.body}
                    />
                </label>
                {selectedRank ? (
                    this.renderMasteriesOnlyForRank(selectedRank)
                ) : (
                    <p className="mt-4">No class masteries are available.</p>
                )}
            </div>
        );
    }

    manageClassSpecialties() {
        this.setState({
            show_class_specialties: !this.state.show_class_specialties,
        });
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
                            <button
                                type="button"
                                aria-label="Close class details"
                                onClick={() => this.manageViewClass(null)}
                            >
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

                        <dl className="mb-4 grid grid-cols-2 gap-x-3 gap-y-2 rounded-sm bg-gray-100 p-3 text-sm dark:bg-gray-900">
                            <dt className="font-semibold">Rank Level</dt>
                            <dd>{this.state.class_name_selected.level}</dd>
                            <dt className="font-semibold">Current XP</dt>
                            <dd>
                                {formatNumber(
                                    this.state.class_name_selected.current_xp,
                                )}
                            </dd>
                            <dt className="font-semibold">Required XP</dt>
                            <dd>
                                {formatNumber(
                                    this.state.class_name_selected.required_xp,
                                )}
                            </dd>
                            <dt className="font-semibold">Active</dt>
                            <dd>
                                {this.state.class_name_selected.is_active
                                    ? "Yes"
                                    : "No"}
                            </dd>
                            <dt className="font-semibold">Locked</dt>
                            <dd>
                                {this.state.class_name_selected.is_locked
                                    ? "Yes"
                                    : "No"}
                            </dd>
                        </dl>

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
                        </div>
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
