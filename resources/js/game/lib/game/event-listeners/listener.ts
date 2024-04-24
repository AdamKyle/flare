export default interface Listener {
    /**
     * Use this to register your listener.
     *
     * This is to be called in the constructor of a react class component.
     */
    register: () => void;

    /**
     * Use this to listen to events from the server.
     *
     * This is to be called in the componentDidMount of a react class component.
     */
    listen: () => void;
}
