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

if (document.getElementById('administrator-chat')) {
  void import('./admin/chat/admin-chat');
}
