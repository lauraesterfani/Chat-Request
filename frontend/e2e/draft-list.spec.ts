import { expect, test } from "@playwright/test";

test("aluno escolhe, retoma e descarta seus rascunhos", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const firstId = "22222222-2222-4222-8222-222222222222";
  const secondId = "33333333-3333-4333-8333-333333333333";
  let drafts = [
    { id: firstId, type_request_id: typeId, form_schema_version_id: null, enrollment_id: null, revision: 1, updated_at: "2026-10-12T12:00:00Z", type: { name: "Ementa QA" }, responses: { subject: "Primeiro assunto", description: "Primeira descrição" } },
    { id: secondId, type_request_id: typeId, form_schema_version_id: null, enrollment_id: null, revision: 2, updated_at: "2026-10-12T13:00:00Z", type: { name: "Ementa QA" }, responses: { subject: "Segundo assunto", description: "Segunda descrição" } },
  ];
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("dialog", (dialog) => dialog.accept());
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: "qa", name: "Aluno QA", role: "student", must_change_password: false } });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Ementa QA", requires_document: false }]);
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ type_id: typeId, schema: { fields: [{ key: "subject", label: "Assunto", type: "short_text", required: true }, { key: "description", label: "Descrição", type: "long_text", required: true }] }, version_id: null });
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json(drafts);
    if (url.pathname === `/api/drafts/${firstId}` && route.request().method() === "DELETE") { drafts = drafts.filter((draft) => draft.id !== firstId); return json({ message: "Rascunho descartado." }); }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/drafts");
  await expect(page.getByRole("heading", { name: "Meus rascunhos" })).toBeVisible();
  await expect(page.getByRole("listitem")).toHaveCount(2);
  await expect(page.getByRole("listitem").first()).toContainText("Primeiro assunto");
  await expect(page.getByRole("listitem").nth(1)).toContainText("Segundo assunto");
  await page.getByRole("button", { name: "Descartar rascunho de Ementa QA" }).first().click();
  await expect(page.getByRole("listitem")).toHaveCount(1);
  await page.getByRole("link", { name: "Continuar" }).click();
  await expect(page).toHaveURL(new RegExp(`draft=${secondId}`));
  await expect(page.getByRole("textbox", { name: /Assunto/ })).toHaveValue("Segundo assunto");
  expect(errors, errors.join("\n")).toEqual([]);
  const width = await page.evaluate(() => ({ document: document.documentElement.scrollWidth, viewport: window.innerWidth }));
  expect(width.document).toBeLessThanOrEqual(width.viewport);
});
