/**
 * Bootstrap the application.
 */
import "./bootstrap";

if (document.getElementById("administrator-chat") !== null) {
    void import("./admin/admin-chat");
}

if (document.getElementById("administrator-statistics") !== null) {
    void import("./admin/statistics-dashboard");
}

if (document.getElementById("info-management") !== null) {
    void import("./admin/info-management/info-management-init");
}

if (document.getElementById("event-calendar") !== null) {
    void import("./admin/calendar");
}

if (document.getElementById("map-manager") !== null) {
    void import("./admin/map-manager-location");
}

if (document.getElementById("character-reward-queue") !== null) {
    void import("./admin/battle-reward-queue");
}

if (document.getElementById("exploration-monitoring") !== null) {
    void import("./admin/exploration-monitoring");
}

if (document.getElementById("faction-loyalty-monitoring") !== null) {
    void import("./admin/faction-loyalty-monitoring");
}

if (document.getElementById("delve-monitoring") !== null) {
    void import("./admin/delve-monitoring");
}

if (document.getElementById("logs-dashboard") !== null) {
    void import("./admin/logs-dashboard");
}
