import React from "react";
import CharacterProfileHeaderProps from "../../types/profile/character-profile-header-props";

export default class CharacterProfileHeader extends React.Component<CharacterProfileHeaderProps> {
    render() {
        return (
            <header className="rounded-sm border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {this.props.overview.name ?? "Character"}
                        </h2>
                        <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            Level {this.props.overview.level ?? 0}{" "}
                            {this.props.overview.race ?? "Unknown Race"}{" "}
                            {this.props.overview.class ?? "Unknown Class"}
                        </p>
                    </div>
                    <div className="grid gap-2 text-sm sm:grid-cols-3">
                        <span className="rounded-sm border border-gray-200 px-3 py-2 dark:border-gray-700">
                            {this.props.overview.current_map ?? "Unknown Map"}
                        </span>
                        <span className="rounded-sm border border-gray-200 px-3 py-2 dark:border-gray-700">
                            {this.props.overview.online ? "Online" : "Offline"}
                        </span>
                        <span className="rounded-sm border border-gray-200 px-3 py-2 dark:border-gray-700">
                            Last active:{" "}
                            {this.props.overview.last_active_at ?? "Unknown"}
                        </span>
                    </div>
                </div>
            </header>
        );
    }
}
