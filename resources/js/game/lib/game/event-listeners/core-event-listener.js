import Echo from "laravel-echo";
var CoreEventListener = (function () {
    function CoreEventListener() {}
    CoreEventListener.prototype.initialize = function () {
        var token = document.head.querySelector('meta[name="csrf-token"]');
        if (token === null) {
            throw new Error("CSRF Token is missing. Failed to initialize.");
        }
        this.echo = new Echo({
            broadcaster: "pusher",
            key: import.meta.env.VITE_PUSHER_APP_KEY,
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
    };
    CoreEventListener.prototype.getEcho = function () {
        if (this.echo) {
            return this.echo;
        }
        throw new Error("Echo has not been initialized.");
    };
    return CoreEventListener;
})();
export default CoreEventListener;
//# sourceMappingURL=core-event-listener.js.map
