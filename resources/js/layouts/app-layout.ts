/**
 * Plain-JavaScript bootstrapping for the shared player header used by both the ordinary
 * (non-admin) Blade shell and the dedicated game layout.
 *
 * Replaces the Alpine-driven mobile menu toggle and dark-mode toggle that previously lived in
 * `resources/js/vendor/alpine.js` + `x-data`/`x-init`/`@click` directives in the Blade templates.
 */

import { initAdminAccountMenu } from './admin/init-admin-account-menu';
import { initAdminDarkMode } from './admin/init-admin-dark-mode';
import { initAdminNavigationGroups } from './admin/init-admin-navigation-groups';
import { initAdminProfileMenu } from './admin/init-admin-profile-menu';
import { initAdminSidebar } from './admin/init-admin-sidebar';

document.addEventListener('DOMContentLoaded', () => {
  initAdminDarkMode();
  initAdminAccountMenu();
  initAdminProfileMenu();
  initAdminSidebar();
  initAdminNavigationGroups();
});
