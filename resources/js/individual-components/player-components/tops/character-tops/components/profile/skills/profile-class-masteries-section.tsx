import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import { asTopsRecordList } from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileClassMasteriesSectionProps from "../../../types/profile/skills/profile-class-masteries-section-props";
import ProfileClassMasteriesSectionState from "../../../types/profile/skills/profile-class-masteries-section-state";
import ProfileClassDetailModal from "./profile-class-detail-modal";
import ProfileSpecialtyDetailModal from "./profile-specialty-detail-modal";

export default class ProfileClassMasteriesSection extends React.Component<
    ProfileClassMasteriesSectionProps,
    ProfileClassMasteriesSectionState
> {
    constructor(props: ProfileClassMasteriesSectionProps) {
        super(props);

        this.state = {
            selectedClass: null,
            selectedSpecialty: null,
        };
    }

    openClass(classRank: Record<string, TopsValue>): void {
        this.setState({
            selectedClass: classRank,
        });
    }

    closeClass(): void {
        this.setState({
            selectedClass: null,
        });
    }

    openSpecialty(specialty: Record<string, TopsValue>): void {
        this.setState({
            selectedSpecialty: specialty,
        });
    }

    closeSpecialty(): void {
        this.setState({
            selectedSpecialty: null,
        });
    }

    classRanks(): Record<string, TopsValue>[] {
        return asTopsRecordList(this.props.skills?.class_ranks);
    }

    renderClassRank(classRank: Record<string, TopsValue>, index: number) {
        const equippedSpecialties = asTopsRecordList(
            classRank.equipped_specialties,
        );
        const unlockedSpecialties = asTopsRecordList(
            classRank.unlocked_specialties,
        );

        return (
            <button
                key={String(classRank.class ?? "class") + index}
                type="button"
                className="rounded-sm border border-gray-200 p-4 text-left transition hover:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700"
                onClick={() => this.openClass(classRank)}
            >
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 className="text-lg font-semibold">
                            {classRank.class ?? "Unknown Class"}
                        </h3>
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            Level {formatTopsValue(classRank.level)} · XP{" "}
                            {formatTopsValue(classRank.current_xp)} /{" "}
                            {formatTopsValue(classRank.required_xp)}
                        </p>
                    </div>
                    {classRank.is_active ? (
                        <span className="rounded-sm bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-100">
                            Active
                        </span>
                    ) : null}
                </div>
                <p className="mt-3 text-sm text-gray-700 dark:text-gray-300">
                    {equippedSpecialties.length} equipped specialties ·{" "}
                    {unlockedSpecialties.length} unlocked specialties ·{" "}
                    {asTopsRecordList(classRank.weapon_masteries).length} weapon
                    masteries
                </p>
            </button>
        );
    }

    render() {
        const classRanks = this.classRanks();

        return (
            <BasicCard additionalClasses="lg:col-span-2">
                <section aria-label="Read-only class masteries">
                    <h2 className="text-xl font-semibold">Class Masteries</h2>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        Public class rank, weapon mastery, and specialty
                        progress.
                    </p>
                    <div className="mt-4 grid gap-3">
                        {classRanks.length === 0 ? (
                            <TopsEmptyState message="No public class mastery progress is available." />
                        ) : (
                            classRanks.map(
                                (
                                    classRank: Record<string, TopsValue>,
                                    index: number,
                                ) => this.renderClassRank(classRank, index),
                            )
                        )}
                    </div>

                    {this.state.selectedClass ? (
                        <ProfileClassDetailModal
                            classRank={this.state.selectedClass}
                            onClose={() => this.closeClass()}
                            onSelectSpecialty={(
                                specialty: Record<string, TopsValue>,
                            ) => this.openSpecialty(specialty)}
                        />
                    ) : null}
                    {this.state.selectedSpecialty ? (
                        <ProfileSpecialtyDetailModal
                            specialty={this.state.selectedSpecialty}
                            onClose={() => this.closeSpecialty()}
                        />
                    ) : null}
                </section>
            </BasicCard>
        );
    }
}
