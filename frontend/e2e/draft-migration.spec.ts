import { expect, test } from "@playwright/test";

test("aluno revisa a mudança de formulário antes de continuar o rascunho", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  const oldId = "33333333-3333-4333-8333-333333333333";
  const newId = "44444444-4444-4444-8444-444444444444";
  const oldFields = [{ key: "reason", label: "Motivo antigo", type: "short_text" }, { key: "old_note", label: "Observação antiga", type: "long_text" }];
  const newFields = [{ key: "reason", label: "Motivo atualizado", type: "short_text" }, { key: "new_note", label: "Nova informação", type: "long_text", required: true }];
  let draft = { id: draftId, type_request_id: typeId, form_schema_version_id: oldId, revision: 2, responses: { subject: "Assunto salvo", description: "Descrição salva", reason: "Motivo preservado", old_note: "Texto removido" }, schema_version: { schema: { fields: oldFields } } };
  let applied = 0;
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: "qa", name: "Aluno QA", role: "student", must_change_password: false } });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Ementa QA", requires_document: false }]);
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json([draft]);
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ type_id: typeId, version_id: newId, schema: { fields: newFields } });
    if (url.pathname === `/api/drafts/${draftId}/migration` && route.request().method() === "GET") return json({ needed: true, to_version_id: newId, to_version: 2, schema: { fields: newFields }, responses: { subject: "Assunto salvo", description: "Descrição salva", reason: "Motivo preservado" }, retained_keys: ["subject", "description", "reason"], reset_keys: ["old_note"], required_keys: ["new_note"] });
    if (url.pathname === `/api/drafts/${draftId}/migration` && route.request().method() === "POST") {
      applied++;
      draft = { ...draft, form_schema_version_id: newId, revision: 3, responses: { subject: "Assunto salvo", description: "Descrição salva", reason: "Motivo preservado", old_note: "" }, schema_version: { schema: { fields: newFields } } };
      return json({ draft: { ...draft, responses: { subject: "Assunto salvo", description: "Descrição salva", reason: "Motivo preservado" } }, migration: { schema: { fields: newFields } } });
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto(`/requests/new?draft=${draftId}`);
  await expect(page.getByRole("heading", { name: "Este formulário foi atualizado" })).toBeVisible();
  await expect(page.getByText("Observação antiga")).toBeVisible();
  await expect(page.getByText("Nova informação", { exact: false })).toBeVisible();
  await expect(page.getByRole("button", { name: "Revisar requerimento" })).toHaveCount(0);
  expect(applied).toBe(0);
  await page.getByRole("button", { name: "Atualizar e revisar rascunho" }).click();
  await expect(page.getByRole("heading", { name: "Este formulário foi atualizado" })).toHaveCount(0);
  await expect(page.getByRole("textbox", { name: "Assunto" })).toHaveValue("Assunto salvo");
  await expect(page.getByRole("textbox", { name: "Motivo atualizado" })).toHaveValue("Motivo preservado");
  await expect(page.getByRole("textbox", { name: "Nova informação" })).toBeVisible();
  expect(applied).toBe(1);
  const width = await page.evaluate(() => ({ document: document.documentElement.scrollWidth, viewport: window.innerWidth }));
  expect(width.document).toBeLessThanOrEqual(width.viewport);
  expect(errors, errors.join("\n")).toEqual([]);
});
