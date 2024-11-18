import Echo from "laravel-echo";

export default class CoreEventListener {
    private echo?: Echo<"pusher">;

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
            broadcaster: "pusher",
            key: (import.meta as any).env.VITE_PUSHER_APP_KEY,
            wsHost: window.location.hostname,
            wsPort: 6001,
            wssPort: 6001,
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
    public getEcho(): Echo<"pusher"> {
        if (this.echo) {
            return this.echo;
        }

        throw new Error("Echo has not been initialized.");
    }
}
