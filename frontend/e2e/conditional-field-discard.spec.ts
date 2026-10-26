import { expect, test } from "@playwright/test";

test("campo condicional removido é avisado e pode ser restaurado antes do envio", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  let draft: Record<string, unknown> | null = null;
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ id: "student-qa", name: "Aluno QA", role: "student", must_change_password: false });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Histórico Escolar", requires_document: false }]);
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ version_id: null, schema: { fields: [
      { key: "request_kind", label: "Finalidade", type: "single_select", required: true, options: ["Outro", "Histórico"] },
      { key: "other_purpose", label: "Informe outra finalidade", type: "short_text", required: true, visible_if: { all: [{ field: "request_kind", operator: "equals", value: "Outro" }] } },
    ] } });
    if (url.pathname === "/api/requests/duplicates/check") return json({ mode: "warn", matches: [] });
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json(draft ? [draft] : []);
    if ((url.pathname === "/api/drafts" || url.pathname === `/api/drafts/${draftId}`) && ["POST", "PUT"].includes(route.request().method())) {
      const body = route.request().postDataJSON() as Record<string, unknown>;
      draft = { ...body, id: draftId, revision: Number(draft?.revision ?? 0) + 1 };
      return json(draft, route.request().method() === "POST" ? 201 : 200);
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/new");
  await page.getByLabel("Tipo de solicitação").selectOption(typeId);
  await page.getByRole("textbox", { name: "Assunto" }).fill("Finalidade acadêmica");
  await page.getByRole("textbox", { name: "Descrição" }).fill("Descrição fictícia para QA.");
  const purpose = page.locator("#form-request_kind");
  await purpose.selectOption("Outro");
  await page.getByLabel("Informe outra finalidade").fill("Solicitação de teste que deve ser descartada.");
  await purpose.selectOption("Histórico");
  await expect(page.getByLabel("Informe outra finalidade")).toHaveCount(0);
  await expect(page.getByText("Uma resposta ficou oculta e foi removida ao mudar a opção.")).toBeVisible();
  await page.getByRole("button", { name: "Desfazer" }).click();
  await expect(purpose).toHaveValue("Outro");
  await expect(page.getByLabel("Informe outra finalidade")).toHaveValue("Solicitação de teste que deve ser descartada.");
  expect(errors, errors.join("\n")).toEqual([]);
});
