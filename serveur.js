import { ServerPageController } from "./frontend/server-page/controllers/ServerPageController.js";

const controller = new ServerPageController();

window.genererLienInvitation = () => controller.generateInviteLink();
window.toggleMenu = () => controller.toggleMenu();
window.logout = () => controller.logout();
window.fermerProfil = () => controller.closeProfile();

controller.init();
