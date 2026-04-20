import { DmApiClient } from "./frontend/dm-page/api/DmApiClient.js";
import { DmPageController } from "./frontend/dm-page/controllers/DmPageController.js";
import { DmStore } from "./frontend/dm-page/stores/DmStore.js";
import { DmView } from "./frontend/dm-page/views/DmView.js";

const controller = new DmPageController({
  apiClient: new DmApiClient(),
  store: new DmStore(),
  view: new DmView()
});

controller.init();
