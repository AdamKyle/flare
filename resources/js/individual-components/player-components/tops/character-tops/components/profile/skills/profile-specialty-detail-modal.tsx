import React from "react";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import ProfileSpecialtyDetailModalProps from "../../../types/profile/skills/profile-specialty-detail-modal-props";

export default class ProfileSpecialtyDetailModal extends React.Component<ProfileSpecialtyDetailModalProps> {
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

    render() {
        const specialty = this.props.specialty;

        return (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="profile-specialty-detail-title"
            >
                <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase text-gray-600 dark:text-gray-400">
                                Class Specialty
                            </p>
                            <h2
                                id="profile-specialty-detail-title"
                                className="text-2xl font-semibold"
                            >
                                {specialty.name ?? "Unknown Specialty"}
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

                    <p className="mt-4 text-sm text-gray-700 dark:text-gray-300">
                        {specialty.description ??
                            "No public description is available."}
                    </p>

                    <dl className="mt-5 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-semibold">Level</dt>
                            <dd>{formatTopsValue(specialty.level)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">XP</dt>
                            <dd>
                                {formatTopsValue(specialty.current_xp)} /{" "}
                                {formatTopsValue(specialty.required_xp)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">Class</dt>
                            <dd>{formatTopsValue(specialty.class_name)}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">
                                Required Rank
                            </dt>
                            <dd>
                                {formatTopsValue(
                                    specialty.requires_class_rank_level,
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">
                                Specialty Damage
                            </dt>
                            <dd>
                                {formatTopsValue(specialty.specialty_damage)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-semibold">
                                Attack Type
                            </dt>
                            <dd>
                                {formatTopsValue(
                                    specialty.attack_type_required,
                                )}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        );
    }
}
