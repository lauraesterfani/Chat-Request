import { expect, test } from "@playwright/test";

test("matrícula escolhida antes do serviço permanece no rascunho", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  const firstEnrollment = "33333333-3333-4333-8333-333333333333";
  const secondEnrollment = "44444444-4444-4444-8444-444444444444";
  let draft: Record<string, unknown> | null = null;
  let savedEnrollmentId: string | null = null;
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
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [
      { id: firstEnrollment, enrollment: "20241TSIIG001", course_id: "tsi", course_name: "TSI" },
      { id: secondEnrollment, enrollment: "20241ADMIG002", course_id: "adm", course_name: "Administração" },
    ], requires_selection: true });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Declaração QA", requires_document: false }]);
    if (url.pathname === "/api/requests/duplicates/check") return json({ mode: "warn", matches: [] });
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ version_id: null, schema: { fields: [] } });
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json(draft ? [draft] : []);
    if ((url.pathname === "/api/drafts" || url.pathname === `/api/drafts/${draftId}`) && ["POST", "PUT"].includes(route.request().method())) {
      const body = route.request().postDataJSON() as Record<string, unknown>;
      savedEnrollmentId = body.enrollment_id as string;
      draft = { ...body, id: draftId, revision: Number(draft?.revision ?? 0) + 1 };
      return json(draft, route.request().method() === "POST" ? 201 : 200);
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/new");
  await page.getByLabel("Matrícula acadêmica").selectOption(secondEnrollment);
  await page.getByLabel("Tipo de solicitação").selectOption(typeId);
  await expect(page.getByLabel("Matrícula acadêmica")).toHaveValue(secondEnrollment);
  await page.getByRole("textbox", { name: "Assunto" }).fill("Pedido da segunda matrícula");
  await page.getByRole("textbox", { name: "Descrição" }).fill("Detalhes do pedido");
  await expect(page.getByText("Rascunho salvo")).toBeVisible();
  expect(savedEnrollmentId).toBe(secondEnrollment);

  await page.goto(`/requests/new?draft=${draftId}`);
  await expect(page.getByLabel("Matrícula acadêmica")).toHaveValue(secondEnrollment);
  await expect(page.getByLabel("Matrícula acadêmica")).toBeDisabled();
  await page.getByRole("button", { name: "Revisar requerimento" }).click();
  await expect(page.getByText("20241ADMIG002 — Administração")).toBeVisible();
  expect(errors, errors.join("\n")).toEqual([]);
});
