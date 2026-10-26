import { expect, test } from "@playwright/test";

test("falha de rede no autosalvamento preserva o texto e permite tentar novamente", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  let saveAttempts = 0;
  let savedResponses: Record<string, unknown> | null = null;
  const failedRequests: Array<{ url: string; reason?: string }> = [];
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("requestfailed", (request) => failedRequests.push({ url: request.url(), reason: request.failure()?.errorText }));
  page.on("console", (message) => {
    if (message.type() === "error" && !message.text().includes("net::ERR_FAILED")) errors.push(message.text());
  });

  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: "student-qa", name: "Aluno QA", role: "student", must_change_password: false } });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Histórico Escolar QA", requires_document: false }]);
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ type_id: typeId, version_id: null, schema: { fields: [] } });
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json([]);
    if (url.pathname === "/api/drafts" && route.request().method() === "POST") {
      saveAttempts += 1;
      if (saveAttempts === 1) return route.abort("failed");
      const body = route.request().postDataJSON() as { responses: Record<string, unknown> };
      savedResponses = body.responses;
      return json({ ...body, id: draftId, revision: saveAttempts }, 201);
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/new");
  await page.getByLabel("Tipo de solicitação").selectOption(typeId);
  const subject = page.locator("#form-subject");
  const description = page.locator("#form-description");
  await subject.fill("Pedido de histórico em rascunho");
  await description.fill("Texto que precisa sobreviver à queda temporária da conexão.");

  await expect(page.getByText(/Sem conexão\. Suas respostas continuam nesta tela/)).toBeVisible({ timeout: 10_000 });
  await expect(subject).toHaveValue("Pedido de histórico em rascunho");
  await expect(description).toHaveValue("Texto que precisa sobreviver à queda temporária da conexão.");

  await description.fill("Texto preservado e reenviado quando a conexão retorna.");
  await expect(page.getByText("Rascunho salvo")).toBeVisible({ timeout: 10_000 });
  await expect.poll(() => saveAttempts).toBe(2);
  expect(savedResponses).toMatchObject({
    subject: "Pedido de histórico em rascunho",
    description: "Texto preservado e reenviado quando a conexão retorna.",
  });
  await expect(subject).toHaveValue("Pedido de histórico em rascunho");
  await expect(description).toHaveValue("Texto preservado e reenviado quando a conexão retorna.");
  expect(errors, errors.join("\n")).toEqual([]);
  expect(failedRequests).toHaveLength(1);
  expect(failedRequests[0].url).toContain("/api/drafts");
  expect(failedRequests[0].reason).toContain("net::ERR_FAILED");
});
