import { expect, test } from "@playwright/test";

test("CRADT pode salvar a política de duplicidade em telas pequenas", async ({ page }) => {
  let saved: Record<string, unknown> | null = null;
  const type = {
    id: "11111111-1111-4111-8111-111111111111", name: "Histórico Escolar",
    description: "Informe a finalidade", requires_document: false, requires_decision: false,
    duplicate_mode: "warn", duplicate_window_days: null, duplicate_rule_reference: null,
  };
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-staff-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ id: "staff-qa", name: "CRADT QA", role: "cradt" });
    if (url.pathname === "/api/admin/type-requests") return json([type]);
    if (url.pathname === `/api/type-requests/${type.id}` && route.request().method() === "PUT") {
      saved = route.request().postDataJSON() as Record<string, unknown>;
      return json({ ...type, ...saved });
    }
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    return json({ message: "Rota de QA não configurada" }, 404);
  });

  await page.goto("/dashboard/admin/types");
  const row = page.getByRole("row", { name: /Histórico Escolar/ });
  await row.getByRole("button", { name: "Editar Histórico Escolar" }).click();
  const dialog = page.getByRole("dialog", { name: "Editar Tipo" });
  await dialog.getByLabel("Período para identificar pedidos parecidos").fill("14");
  await dialog.getByLabel("Ação quando houver correspondência").selectOption("block");
  await dialog.getByLabel("Referência da regra aprovada").fill("Regra de homologação QA");
  const save = dialog.getByRole("button", { name: "Salvar" });
  await expect(save).toBeVisible();
  await save.scrollIntoViewIfNeeded();
  await expect(save).toBeInViewport();
  await save.click();
  await expect.poll(() => saved).toMatchObject({ duplicate_mode: "block", duplicate_window_days: 14, duplicate_rule_reference: "Regra de homologação QA" });
  expect(errors, errors.join("\n")).toEqual([]);
});
