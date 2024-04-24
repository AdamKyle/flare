import React, { Fragment } from "react";
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import { watchForDarkModeClassRankChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import CharacterClassRanksState from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/types/character-class-ranks-state";
import ClassRankType from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";
import WeaponMastery from "../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/weapon-mastery";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";

export default class CharacterClassRanks extends React.Component<
    any,
    CharacterClassRanksState
> {
    constructor(props: any) {
        super(props);

        this.state = {
            class_ranks: [],
            dark_tables: false,
            loading: true,
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
                                const response = error.response;

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

    tableColumns() {
        return [
            {
                name: "Class name",
                selector: (row: { class_name: string }) => row.class_name,
                cell: (row: any) => (
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
                cell: (row: any) => (
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
                cell: (row: any) => <span>{row.is_active ? "Yes" : "No"}</span>,
            },
            {
                name: "Is Locked",
                selector: (row: { is_locked: boolean }) => row.is_locked,
                cell: (row: any) => <span>{row.is_locked ? "Yes" : "No"}</span>,
            },
            {
                name: "Action",
                selector: (row: ClassRankType) => row.id,
                cell: (row: ClassRankType) => (
                    <span>
                        <PrimaryButton
                            button_label={"Switch To"}
                            on_click={() => this.switchClass(row.game_class_id)}
                            disabled={row.is_active || row.is_locked}
                        />
                    </span>
                ),
            },
        ];
    }

    masteryTableColumns() {
        return [
            {
                name: "Mastery Name",
                selector: (row: WeaponMastery) => row.mastery_name,
            },
            {
                name: "Level",
                selector: (row: WeaponMastery) => row.level,
                sortable: true,
                cell: (row: any) => (
                    <span>{formatNumber(row.level) + "/" + 100}</span>
                ),
            },
            {
                name: "XP",
                selector: (row: WeaponMastery) => row.current_xp,
                cell: (row: any) => (
                    <span>
                        {formatNumber(row.current_xp) +
                            "/" +
                            formatNumber(row.required_xp)}
                    </span>
                ),
            },
        ];
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

        return (
            <div className="max-h-[375px] overflow-y-auto">
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
                                <h3 className="my-3">Base Information</h3>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                <dl className="mb-4">
                                    <dt>Base Damage Stat</dt>
                                    <dd>
                                        {
                                            this.state.class_name_selected
                                                .game_class.to_hit_stat
                                        }
                                    </dd>
                                    <dt>Accuracy Mod</dt>
                                    <dd>
                                        +
                                        {(
                                            this.state.class_name_selected
                                                .game_class.accuracy_mod * 100
                                        ).toFixed(2)}
                                        %
                                    </dd>
                                    <dt>Looting Mod</dt>
                                    <dd>
                                        +
                                        {(
                                            this.state.class_name_selected
                                                .game_class.accuracy_mod * 100
                                        ).toFixed(2)}
                                        %
                                    </dd>
                                </dl>

                                {this.state.class_name_selected
                                    .secondary_class_name !== null &&
                                this.state.class_name_selected
                                    .primary_class_name !== null ? (
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
                    </div>
                ) : (
                    <Fragment>
                        {this.state.switching_class ? (
                            <LoadingProgressBar />
                        ) : null}
                        {this.state.error_message !== null ? (
                            <DangerAlert additional_css={"my-4"}>
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}
                        {this.state.success_message !== null ? (
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
