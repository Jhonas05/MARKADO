import { defineRailway, project, service } from "railway/iac";

export const partial = "markado-app";

export default defineRailway(() => {
  const app = service("markado-app", {
    healthcheck: "/up",
    healthcheckTimeout: 300,
    preDeploy: "php artisan migrate --force",
  });

  return project("MARKADO", {
    resources: [app],
    variables: { managed: false },
  });
});
