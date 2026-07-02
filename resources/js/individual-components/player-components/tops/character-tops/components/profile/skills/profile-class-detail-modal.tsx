import React from "react";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import { asTopsRecordList } from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileClassDetailModalProps from "../../../types/profile/skills/profile-class-detail-modal-props";

export default class ProfileClassDetailModal extends React.Component<ProfileClassDetailModalProps> {
    componentDidMount(): void {
        document.addEventListener("keydown", this.handleKeyDown);
    }

    componentWillUnmount(): void {
        document.removeEventListener("keydown", this.handleKeyDown);
    }

    handleKeyDown = (event: KeyboardEvent): void => {
        if (event.key === "Escape") {
            this.props.onClose();
        }
    };

    renderMastery(mastery: Record<string, TopsValue>, index: number) {
        return (
            <li
                key={String(mastery.weapon_type ?? "mastery") + index}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                <p className="font-semibold">
                    {mastery.name ?? "Unknown Mastery"}
                </p>
                <p className="text-sm text-gray-700 dark:text-gray-300">
                    Level {formatTopsValue(mastery.level)} · XP{" "}
                    {formatTopsValue(mastery.current_xp)} /{" "}
                    {formatTopsValue(mastery.required_xp)}
                </p>
            </li>
        );
    }

    renderSpecialty(
        specialty: Record<string, TopsValue>,
        index: number,
        label: string,
    ) {
        return (
            <button
                key={String(specialty.id ?? label) + index}
                type="button"
                className="rounded-sm border border-gray-200 p-3 text-left transition hover:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700"
                onClick={() => this.props.onSelectSpecialty(specialty)}
            >
                <span className="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {label}
                </span>
                <span className="block font-semibold">
                    {specialty.name ?? "Unknown Specialty"}
                </span>
                <span className="block text-sm text-gray-700 dark:text-gray-300">
                    Level {formatTopsValue(specialty.level)}
                </span>
            </button>
        );
    }

    render() {
        const classRank = this.props.classRank;
        const weaponMasteries = asTopsRecordList(classRank.weapon_masteries);
        const equippedSpecialties = asTopsRecordList(
            classRank.equipped_specialties,
        );
        const unlockedSpecialties = asTopsRecordList(
            classRank.unlocked_specialties,
        );

        return (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="profile-class-detail-title"
            >
                <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase text-gray-600 dark:text-gray-400">
                                Class Mastery
                            </p>
                            <h2
                                id="profile-class-detail-title"
                                className="text-2xl font-semibold"
                            >
                                {classRank.class ?? "Unknown Class"}
                            </h2>
                        </div>
                        <button
                            type="button"
                            className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                            onClick={this.props.onClose}
                        >
                            Close
                        </button>
                    </div>

                    <dl className="mt-5 grid gap-3 sm:grid-cols-3">
                        <div>
                            <dt className="text-sm font-semibold">Level</dt>
                            <dd>{formatTopsValue(classRank.level)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">XP</dt>
                            <dd>
                                {formatTopsValue(classRank.current_xp)} /{" "}
                                {formatTopsValue(classRank.required_xp)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">Active</dt>
                            <dd>
                                {classRank.is_active ? "Active" : "Inactive"}
                            </dd>
                        </div>
                    </dl>

                    <div className="mt-6 grid gap-5 lg:grid-cols-2">
                        <section>
                            <h3 className="font-semibold">Weapon Masteries</h3>
                            <ul className="mt-3 grid gap-2">
                                {weaponMasteries.length === 0 ? (
                                    <li className="text-sm text-gray-700 dark:text-gray-300">
                                        No public weapon masteries.
                                    </li>
                                ) : (
                                    weaponMasteries.map(
                                        (
                                            mastery: Record<string, TopsValue>,
                                            index: number,
                                        ) => this.renderMastery(mastery, index),
                                    )
                                )}
                            </ul>
                        </section>
                        <section>
                            <h3 className="font-semibold">Specialties</h3>
                            <div className="mt-3 grid gap-2">
                                {equippedSpecialties.map(
                                    (
                                        specialty: Record<string, TopsValue>,
                                        index: number,
                                    ) =>
                                        this.renderSpecialty(
                                            specialty,
                                            index,
                                            "Active",
                                        ),
                                )}
                                {unlockedSpecialties.map(
                                    (
                                        specialty: Record<string, TopsValue>,
                                        index: number,
                                    ) =>
                                        this.renderSpecialty(
                                            specialty,
                                            index,
                                            "Unlocked",
                                        ),
                                )}
                                {equippedSpecialties.length === 0 &&
                                unlockedSpecialties.length === 0 ? (
                                    <p className="text-sm text-gray-700 dark:text-gray-300">
                                        No public specialties for this class.
                                    </p>
                                ) : null}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        );
    }
}
