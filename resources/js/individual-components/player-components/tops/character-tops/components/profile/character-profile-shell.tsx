import React from "react";
import CharacterProfileShellProps from "../../types/profile/character-profile-shell-props";
import TopsCharacterSheetInspect from "./sheet-inspect/tops-character-sheet-inspect";
import ProfileActivitySection from "./activity/profile-activity-section";
import ProfileAnalyticsSection from "./analytics/profile-analytics-section";
import ProfileQuestsSection from "./quests/profile-quests-section";
import ProfileKingdoms from "../profile-kingdoms";
import { formatLocalDateTime } from "../../../../../../game/lib/game/format-local-date";

export default class CharacterProfileShell extends React.Component<CharacterProfileShellProps> {
    renderStatusBanner(overview: Record<string, any>) {
        return (
            <section
                className="rounded-sm border border-yellow-300 bg-yellow-50 p-4 shadow-sm dark:border-yellow-700 dark:bg-yellow-950/40"
                aria-label="Character public status"
            >
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-yellow-950 dark:text-yellow-100">
                            {overview.name ?? "Unknown Character"}
                        </h2>
                        <p className="text-sm text-yellow-800 dark:text-yellow-200">
                            Current public status for this character inspect.
                        </p>
                    </div>
                    <dl className="grid gap-3 text-sm sm:grid-cols-3 lg:min-w-[560px]">
                        <div>
                            <dt className="font-semibold text-yellow-900 dark:text-yellow-100">
                                Status
                            </dt>
                            <dd className="text-yellow-950 dark:text-yellow-50">
                                {overview.online ? "Online" : "Offline"}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-yellow-900 dark:text-yellow-100">
                                Current Map
                            </dt>
                            <dd className="text-yellow-950 dark:text-yellow-50">
                                {overview.current_map ?? "Unknown"}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-yellow-900 dark:text-yellow-100">
                                Last Active
                            </dt>
                            <dd className="text-yellow-950 dark:text-yellow-50">
                                {formatLocalDateTime(overview.last_active_at)}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        );
    }

    render() {
        const overview = this.props.profile.overview ?? {};

        return (
            <div className="space-y-4">
                {this.renderStatusBanner(overview)}
                <TopsCharacterSheetInspect profile={this.props.profile} />
                <section
                    aria-label="Additional public profile details"
                    className="space-y-4"
                >
                    <ProfileQuestsSection quests={this.props.profile.quests} />
                    <ProfileKingdoms kingdoms={this.props.profile.kingdoms} />
                    <ProfileAnalyticsSection
                        analytics={this.props.profile.analytics}
                    />
                    <ProfileActivitySection
                        activity={this.props.profile.activity}
                    />
                </section>
            </div>
        );
    }
}
