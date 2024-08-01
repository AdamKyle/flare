import Echo from "laravel-echo";

export const config = {
    // @ts-ignore
    VITE_REVERB_APP_KEY: import.meta.env.VITE_REVERB_APP_KEY,
    // @ts-ignore
    VITE_REVERB_HOST: import.meta.env.VITE_REVERB_HOST,
    // @ts-ignore
    VITE_REVERB_PORT: import.meta.env.VITE_REVERB_PORT,
    // @ts-ignore
    VITE_REVERB_SCHEME: import.meta.env.VITE_REVERB_SCHEME,
};

export default class CoreEventListener {
    private echo?: Echo;

    /**
     * Initialize Laravel Echo
     *
     * @throws Error - if csrf token is missing.
     */
    public initialize(): void {
        let token: HTMLMetaElement | null = document.head.querySelector(
            'meta[name="csrf-token"]',
        );

        if (token === null) {
            throw new Error("CSRF Token is missing. Failed to initialize.");
        }

        this.echo = new Echo({
            broadcaster: "reverb",
            key: config.VITE_REVERB_APP_KEY,
            wsHost: config.VITE_REVERB_HOST,
            wsPort: config.VITE_REVERB_PORT ?? 80,
            wssPort: config.VITE_REVERB_PORT ?? 443,
            forceTLS: (config.VITE_REVERB_SCHEME ?? "https") === "https",
            enabledTransports: ["ws", "wss"],
            namespace: "App",
            auth: {
                headers: {
                    "X-CSRF-TOKEN": token.content,
                },
            },
        });
    }

    /**
     * Fetch an instance of echo.
     *
     * @throws Error - if echo is not initialized.
     */
    public getEcho(): Echo {
        if (this.echo) {
            return this.echo;
        }

        throw new Error("Echo has not been initialized.");
    }
}
