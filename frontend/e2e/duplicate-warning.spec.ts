import { expect, test } from "@playwright/test";

test("avisa sobre um pedido próprio e exige confirmação antes de continuar", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  const existingRequestId = "33333333-3333-4333-8333-333333333333";
  const createdRequestId = "44444444-4444-4444-8444-444444444444";
  let draft: Record<string, unknown> | null = null;
  let duplicateAcknowledged: unknown;
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });

  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: "student-qa", name: "Aluno QA", role: "student", must_change_password: false } });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Histórico Escolar", requires_document: false }]);
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ version_id: null, schema: { fields: [] } });
    if (url.pathname === "/api/requests/duplicates/check") return json({ mode: "warn", matches: [{ id: existingRequestId, protocol: "20261026-1234", subject: "Histórico Escolar", status: "pending", created_at: "2026-10-26T12:00:00Z" }] });
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json(draft ? [draft] : []);
    if ((url.pathname === "/api/drafts" || url.pathname === `/api/drafts/${draftId}`) && ["POST", "PUT"].includes(route.request().method())) {
      const body = route.request().postDataJSON() as Record<string, unknown>;
      draft = { ...body, id: draftId, revision: Number(draft?.revision ?? 0) + 1 };
      return json(draft, route.request().method() === "POST" ? 201 : 200);
    }
    if (url.pathname === "/api/requests" && route.request().method() === "POST") {
      duplicateAcknowledged = (route.request().postDataJSON() as Record<string, unknown>).duplicate_acknowledged;
      return json({ id: createdRequestId }, 201);
    }
    if (url.pathname === `/api/requests/${createdRequestId}`) return json({
      id: createdRequestId, protocol: "20261026-654321", subject: "Histórico Escolar",
      description: "Preciso do histórico para análise.", status: "pending",
      type: { name: "Histórico Escolar" }, documents: [],
      user: { name: "Aluno QA", email: "aluno@example.test", matricula: "QA-2026", course: { name: "Curso de QA" } },
    });
    if (url.pathname === `/api/requests/${createdRequestId}/messages`) return json({ data: [], current_page: 1, last_page: 1 });
    if (url.pathname === `/api/requests/${createdRequestId}/messages/read`) return json({ message: "Lidas" });
    if (url.pathname === `/api/requests/${createdRequestId}/events`) return json({ data: [] });
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/new");
  await page.getByLabel("Tipo de solicitação").selectOption(typeId);
  await page.getByRole("textbox", { name: "Assunto" }).fill("Histórico Escolar");
  await page.getByRole("textbox", { name: "Descrição" }).fill("Preciso do histórico para análise.");
  await page.getByRole("button", { name: "Revisar requerimento" }).click();
  await expect(page.getByRole("heading", { name: "Encontramos pedidos seus ainda em aberto com o mesmo assunto" })).toBeVisible();
  await expect(page.getByRole("link", { name: /Protocolo 20261026-1234/ })).toHaveAttribute("href", `/requests/visualizar/${existingRequestId}`);
  const confirm = page.getByRole("button", { name: "Confirmar envio" });
  await expect(confirm).toBeDisabled();
  await page.getByLabel("Revisei os pedidos acima e desejo continuar com uma nova solicitação.").check();
  await expect(confirm).toBeEnabled();
  await confirm.click();
  await expect.poll(() => duplicateAcknowledged).toBe(true);
  await expect(page).toHaveURL(new RegExp(`/requests/visualizar/${createdRequestId}$`));
  await expect(page.getByText("20261026-654321")).toBeVisible();
  await expect(page.getByText("Preciso do histórico para análise.")).toBeVisible();
  expect(errors, errors.join("\n")).toEqual([]);
});
