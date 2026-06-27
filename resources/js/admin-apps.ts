/**
 * Bootstrap the application.
 */
import './bootstrap';

/**
 * Admin app: Guide Quest Form
 *
 * - Used for editing and creating Guide Quests.
 */
import './admin/guide-quests/manage-guide-quest-base';

if (document.getElementById('character-reward-queue') !== null) {
  void import('./admin/battle-reward-queue');
}

if (document.getElementById('exploration-monitoring') !== null) {
  void import('./admin/exploration-monitoring');
}

if (document.getElementById('faction-loyalty-monitoring') !== null) {
  void import('./admin/faction-loyalty-monitoring');
}

if (document.getElementById('delve-monitoring') !== null) {
  void import('./admin/delve-monitoring');
}

if (document.getElementById('logs-dashboard') !== null) {
  void import('./admin/logs-dashboard');
}
