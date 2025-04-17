import { useEventSystem } from "event-system/hooks/use-event-system";
import { useEffect, useState } from "react";

import UseGameLoaderVisibilityDefinition from "./definitions/use-game-loader-visibility-definition";
import GameLoaderVisibility from "./types/use-game-loader-visibility";
import { GameLoaderEvents } from "../game-loader/event-types/game-loader-events";

export const useGameLoaderVisibility =
    (): UseGameLoaderVisibilityDefinition => {
        const eventSystem = useEventSystem();

        const [showGameLoader, setShowGameLoader] =
            useState<GameLoaderVisibility["showGameLoader"]>(true);

        const gameLoaderVisibility = eventSystem.fetchOrCreateEventEmitter<{
            [key: string]: boolean;
        }>(GameLoaderEvents.SHOW_GAME_LOADER);

        useEffect(() => {
            const updateVisibility = (visible: boolean) => {
                setShowGameLoader(visible);
            };

            gameLoaderVisibility.on(
                GameLoaderEvents.SHOW_GAME_LOADER,
                updateVisibility,
            );

            return () => {
                gameLoaderVisibility.off(
                    GameLoaderEvents.SHOW_GAME_LOADER,
                    updateVisibility,
                );
            };
        }, [gameLoaderVisibility]);

        return { showGameLoader };
    };
