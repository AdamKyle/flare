import React from "react";
import { OnlineCharacter } from "../types/admin-statistics-dashboard";

interface OnlineCharacterListProps {
    characters: OnlineCharacter[];
    definition: string;
}

export default class OnlineCharacterList extends React.Component<OnlineCharacterListProps> {
    renderDate(value: string | null): string {
        if (!value) {
            return "Unknown";
        }

        return new Date(value).toLocaleString();
    }

    render() {
        return (
            <section
                className="rounded-sm bg-white p-4 shadow dark:bg-gray-800"
                aria-labelledby="online-characters-heading"
            >
                <h2
                    id="online-characters-heading"
                    className="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Online Characters
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {this.props.definition}
                </p>

                {this.props.characters.length === 0 ? (
                    <p className="mt-4 rounded-sm bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        No characters have an open login duration row.
                    </p>
                ) : (
                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <caption className="sr-only">
                                Characters currently online
                            </caption>
                            <thead>
                                <tr>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Character
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        User
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Level
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Map
                                    </th>
                                    <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                        Last activity
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {this.props.characters.map((character) => (
                                    <tr key={character.character_name}>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {character.character_name}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {character.user_name}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {character.level}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {character.map ?? "Unknown"}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 dark:border-gray-700">
                                            {this.renderDate(
                                                character.last_activity,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        );
    }
}
