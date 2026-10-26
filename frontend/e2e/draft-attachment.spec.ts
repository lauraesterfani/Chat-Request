import { expect, test } from "@playwright/test";

test("múltiplos anexos privados permanecem no rascunho após recarregar", async ({ page }) => {
  const typeId = "11111111-1111-4111-8111-111111111111";
  const draftId = "22222222-2222-4222-8222-222222222222";
  const documentIds = ["33333333-3333-4333-8333-333333333333", "44444444-4444-4444-8444-444444444444"];
  let draft: Record<string, unknown> | null = null;
  let storedDocumentIds: string[] = [];
  let uploadCount = 0;
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });
  await page.addInitScript(() => sessionStorage.setItem("jwt_token", "qa-student-token"));
  await page.route("**/api/**", async (route) => {
    const url = new URL(route.request().url());
    const json = (value: unknown, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(value) });
    if (url.pathname === "/api/me") return json({ user: { id: "qa", name: "Aluno QA", role: "student", must_change_password: false } });
    if (url.pathname === "/api/enrollments/active") return json({ enrollments: [], requires_selection: false });
    if (url.pathname === "/api/notifications") return json({ data: [] });
    if (url.pathname === "/api/notifications/count") return json({ unread: 0 });
    if (url.pathname === "/api/type-requests") return json([{ id: typeId, name: "Atestado QA", requires_document: true, document_instructions: "Envie o comprovante." }]);
    if (url.pathname === "/api/requests/duplicates/check") return json({ mode: "warn", matches: [] });
    if (url.pathname === `/api/type-requests/${typeId}/form`) return json({ version_id: null, schema: { fields: [] } });
    if (url.pathname === "/api/documents/upload" && route.request().method() === "POST") {
      const id = documentIds[uploadCount];
      const name = uploadCount === 0 ? "comprovante.pdf" : "declaracao.pdf";
      uploadCount++;
      return json({ id, name, mime_type: "application/pdf", file_size: 32 }, 201);
    }
    if (url.pathname === "/api/drafts" && route.request().method() === "GET") return json(draft ? [draft] : []);
    if ((url.pathname === "/api/drafts" || url.pathname === `/api/drafts/${draftId}`) && ["POST", "PUT"].includes(route.request().method())) {
      const body = route.request().postDataJSON() as Record<string, unknown>;
      storedDocumentIds = body.document_ids as string[] ?? [];
      draft = { ...body, id: draftId, revision: Number(draft?.revision ?? 0) + 1, attached_documents: (body.document_ids as string[] ?? []).map((id) => ({ id, name: id === documentIds[0] ? "comprovante.pdf" : "declaracao.pdf", mime_type: "application/pdf", file_size: 32 })) };
      return json(draft, route.request().method() === "POST" ? 201 : 200);
    }
    return json({ message: "Rota QA não configurada" }, 404);
  });

  await page.goto("/requests/new");
  await page.getByLabel("Tipo de solicitação").selectOption(typeId);
  await page.getByRole("textbox", { name: "Assunto" }).fill("Pedido com comprovante");
  await page.getByRole("textbox", { name: "Descrição" }).fill("Descrição do pedido");
  await page.locator("#request-document").setInputFiles([
    { name: "comprovante.pdf", mimeType: "application/pdf", buffer: Buffer.from("%PDF-1.4 test") },
    { name: "declaracao.pdf", mimeType: "application/pdf", buffer: Buffer.from("%PDF-1.4 test") },
  ]);
  await expect(page.getByText("Anexo salvo: comprovante.pdf")).toBeVisible();
  await expect(page.getByText("Anexo salvo: declaracao.pdf")).toBeVisible();
  await expect(page.getByText("2 anexos salvos")).toBeVisible();
  await expect(page.getByText("Rascunho salvo")).toBeVisible();
  expect(uploadCount).toBe(2);
  expect(storedDocumentIds).toEqual(documentIds);

  await page.goto(`/requests/new?draft=${draftId}`);
  await expect(page.getByText("Anexo salvo: comprovante.pdf")).toBeVisible();
  await expect(page.getByText("Anexo salvo: declaracao.pdf")).toBeVisible();
  await expect(page.getByRole("textbox", { name: "Assunto" })).toHaveValue("Pedido com comprovante");
  await page.getByRole("button", { name: "Remover do rascunho" }).nth(1).click();
  await expect(page.getByText("Rascunho salvo")).toBeVisible();
  expect(storedDocumentIds).toEqual([documentIds[0]]);
  await page.getByRole("button", { name: "Revisar requerimento" }).click();
  await expect(page.getByRole("heading", { name: "Revise seu requerimento" })).toBeVisible();
  await expect(page.getByText("comprovante.pdf", { exact: true })).toBeVisible();
  expect(uploadCount).toBe(2);
  const width = await page.evaluate(() => ({ document: document.documentElement.scrollWidth, viewport: window.innerWidth }));
  expect(width.document).toBeLessThanOrEqual(width.viewport);
  expect(errors, errors.join("\n")).toEqual([]);
});
